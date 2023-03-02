<?php

namespace Corals\Modules\Etsy\Jobs;

use Corals\Modules\Marketplace\Http\Requests\{ProductRequest};
use Corals\Modules\Marketplace\Models\{Attribute, AttributeOption, AttributeSet, Category, Product};
use Corals\Modules\Marketplace\Services\{ProductService, SKUService};
use Corals\Modules\Marketplace\Traits\ImportTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\{Arr, Str};
use League\Csv\{Exception as CSVException};

class HandleProductsImportFile implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use ImportTrait;

    protected $importFilePath;
    protected $storeId;

    /**
     * @var Collection
     */
    protected $attributes;

    protected $category_id;

    /**
     * @var Collection
     */
    protected $attributeSet;

    /**
     * @var array
     */
    protected $importHeaders;
    protected $user;
    protected $images_root;
    protected $clearExistingImages;

    /**
     * @var
     */
    protected $recordAttributeValues;

    protected $recordAttributes;

    protected $skuService;

    /**
     * @param $importFilePath
     * @param $clearExistingImages
     * @param $storeId
     * @param $user
     */
    public function __construct($importFilePath, $clearExistingImages, $storeId, $user)
    {
        $this->user = $user;
        $this->importFilePath = $importFilePath;
        $this->clearExistingImages = $clearExistingImages;
        $this->skuService = new SKUService();
        $this->importHeaders = [
            'TITLE',
            'DESCRIPTION',
            'PRICE',
            'CURRENCY_CODE',
            'QUANTITY',
            'TAGS',
            'MATERIALS',
            'IMAGE1',
            'IMAGE2',
            'IMAGE3',
            'IMAGE4',
            'IMAGE5',
            'IMAGE6',
            'IMAGE7',
            'IMAGE8',
            'IMAGE9',
            'IMAGE10',
            'VARIATION 1 TYPE',
            'VARIATION 1 NAME',
            'VARIATION 1 VALUES',
            'VARIATION 2 TYPE',
            'VARIATION 2 NAME',
            'VARIATION 2 VALUES',
            'SKU',
        ];

        $this->storeId = $storeId;
    }

    /**
     * @throws CSVException
     */
    public function handle()
    {
        $this->doImport();
    }

    /**
     * @param $record
     * @throws \Exception
     */
    protected function handleImportRecord($record)
    {
        $record = array_map('trim', $record);

        //prepare product data;
        $productData = $productRequestData = $this->getProductData($record);

        //validate record
        $this->validateRecord($productRequestData);

        $productCode = data_get($productData, 'product_code');

        $productModel = Product::query()
            ->where('product_code', $productCode)
            ->first();


        $productRequest = new ProductRequest();

        $productRequest->replace($productRequestData);

        $productService = new ProductService();

        if (isset($productModel) && $productModel) {
            $productModel = $productService->update($productRequest, $productModel);
        } else {
            $productModel = $productService->store($productRequest, Product::class);
        }


        $this->attributeSet->productAttributes()->syncWithoutDetaching($this->attributes);

        $this->generateSKUs($record, $productModel);

        $this->handleProductImages($record, $productModel);

//        if ($productData['type'] === 'variable') {
//        }
    }

    /**
     * @param $record
     * @param $productModel
     */
    protected function generateSKUs($record, $productModel)
    {
        $optionValues = [];

        $this->recordAttributes->each(function ($attribute) use (&$optionValues) {
            $attrOptions = AttributeOption::query()->where('attribute_id', $attribute->id)
                ->whereIn('option_value', $this->recordAttributeValues)
                ->get();

            if ($attrOptions) {
                $optionValues[$attribute->id] = $attrOptions->pluck('id')->toArray();
            }
        });

        if (! $optionValues) {
            return;
        }

        $data = [
            'options' => $optionValues,
            'generate_option' => 'apply_single',
        ];


        $this->skuService->generateSKUs($data, $productModel, [
            'status' => 'active',
            'regular_price' => data_get($record, 'PRICE'),
        ], true);
    }

    /**
     * @param $record
     * @return array
     */
    protected function handleProductCategories($record): array
    {
        return [$this->category_id];
    }

    /**
     * @param $record
     * @param $productModel
     */
    protected function handleProductImages($record, $productModel)
    {
        if ($this->clearExistingImages) {
            $productModel->clearMediaCollection($productModel->galleryMediaCollection);
        }

        $index = 1;

        while (key_exists("IMAGE$index", $record)) {
            $imageURL = trim(data_get($record, "IMAGE$index"));

            if (! $imageURL) {
                $index++;

                continue;
            }

            $productModel->addMediaFromUrl($imageURL)
                ->withCustomProperties([
                    'root' => "marketplace/{$this->storeId}/product_{$productModel->id}",
                    'featured' => $index === 1,
                ])
                ->toMediaCollection($productModel->galleryMediaCollection);

            $index++;
        }
    }

    protected function handleVariationOptions($record)
    {
        $materials = $record['MATERIALS'];
        $this->recordAttributeValues = [];
        $this->recordAttributes = collect();
        $attributes = collect();

        $attribute = $this->getOrCreateAttribute('material', $materials);

        if ($attribute !== false) {
            $attributes->push($attribute);
        }

        $index = 1;

        while (key_exists("VARIATION $index NAME", $record)) {
            $attributeCode = data_get($record, "VARIATION $index NAME");
            $attributeOptions = data_get($record, "VARIATION $index VALUES");

            $attribute = $this->getOrCreateAttribute($attributeCode, $attributeOptions);

            if ($attribute !== false) {
                $attributes->push($attribute);
            }

            $index++;
        }

        return $attributes->pluck('id')->unique()->toArray();
    }

    protected function attributeCodeCleanUp($code)
    {
        return trim(strtolower(Str::singular(Str::afterLast($code, ' '))));
    }

    protected function getOrCreateAttribute($code, $values)
    {
        $code = $this->attributeCodeCleanUp($code);

        if (! $code) {
            return false;
        }

        if (! is_array($values)) {
            $values = explode(',', $values);
        }

        /**
         * @var Attribute
         */
        $attribute = $this->attributes->where('code', $code)->first();


        if (! $attribute) {
            $attribute = Attribute::query()->create([
                'code' => $code,
                'label' => Str::title($code),
                'type' => 'select',
                'display_order' => 1,
//                'store_id' => $this->storeId,
            ]);

            $this->attributes->push($attribute);
        }


        $options = [];

        foreach ($values as $id => $value) {
            //prevent duplicated value.
            $isValueAlreadyExists = AttributeOption::query()
                ->where([
                    ['attribute_id', '=', $attribute->id],
                    ['option_value', '=', $value],
                ])->exists();

            $this->recordAttributeValues[] = $value;

            if ($isValueAlreadyExists) {
                continue;
            }

            $options[] = [
                "option_value" => $value,
                "option_display" => $value,
                "option_order" => $id,
            ];
        }

        if ($options) {
            $attribute->options()->createMany($options);
        }

        $this->recordAttributes->push($attribute);

        return $attribute;
    }

    /**
     * @param $record
     * @return array
     */
    protected function handleProductVariationOptions($record)
    {
        //not used for now
        return [];
    }

    /**.
     * @param $attributeModel
     * @param $value
     * @return mixed
     * @throws \Exception
     */
    protected function getAttributeOption($attributeModel, $value)
    {
        $option = $attributeModel->options->where('option_value', $value)->first();

        if (! $option) {
            throw new \Exception("Attribute {$attributeModel->code} $value option not found");
        }

        return $option;
    }

    /**
     * @param $record
     * @return array
     */
    protected function getShippingDetails($record): array
    {
        return array_filter([
            'shipping_option' => 'calculate_rates',
            'enabled' => data_get($record, 'Shippable') == 1 ? 1 : 0,
            'width' => data_get($record, 'Width'),
            'height' => data_get($record, 'Height'),
            'length' => data_get($record, 'Length'),
            'weight' => data_get($record, 'Weight'),
        ]);
    }

    /**
     * @param $record
     * @return array
     * @throws \Exception
     */
    protected function getProductData($record)
    {
        $productCategories = $this->handleProductCategories($record);

        $variationOptions = $this->handleVariationOptions($record);

        $productAttributes = $this->handleProductVariationOptions($record);

        $productAttributeSets = $this->handleAttributeSets($record);


        return array_filter([
            'name' => $title = data_get($record, 'TITLE'),
            'caption' => Str::limit(data_get($record, 'DESCRIPTION')),
            'product_code' => substr(md5($title), 0, 20),
            'type' => 'variable',
            'status' => 'active',
            'regular_price' => data_get($record, 'PRICE'),
            'allowed_quantity' => '0',
            'categories' => $productCategories,
            'description' => data_get($record, 'DESCRIPTION'),
            'attribute_sets' => $productAttributeSets,
            'set_attribute_options' => $productAttributes,
            'variation_options' => $variationOptions,

            //TODO
//            'inventory' => data_get($record, 'Inventory'),
//            'inventory_value' => data_get($record, 'Inventory Value'),

            'store_id' => $this->storeId,
        ]);
    }

    /**
     * @param array $productData
     * @return array
     */
    protected function getSKUData(array $productData)
    {
        $skuData = Arr::only($productData, [
            'regular_price',
            'sale_price',
            'allowed_quantity',
            'code',
            'status',
            'inventory',
            'inventory_value',
            'shipping',
            'product_id',
        ]);

        foreach ($productData['variation_options'] as $optionId => $option) {
            if (is_array($option)) {
                $key = key($option);
                if ($key == 'multi') {
                    foreach ($option[$key] as $multiOption) {
                        $skuData['options'][$optionId][] = key($multiOption);
                    }
                } else {
                    $skuData['options'][$optionId] = $key;
                }
            } else {
                $skuData['options'][$optionId] = $option;
            }
        }

        return $skuData;
    }

    protected function initHandler()
    {
        $uncategorizedCategory = Category::query()->firstOrCreate(['slug' => 'uncategorized'], [
            'name' => 'Uncategorized',
            'status' => 'active',
        ]);

        $this->category_id = $uncategorizedCategory->id;

        $this->attributeSet = AttributeSet::query()->firstOrCreate(['code' => 'etsy-set'], [
            'name' => 'Etsy Set',
        ]);

        $this->setAttributesList();
    }

    public function setAttributesList()
    {
        $this->attributes = Attribute::query()
            ->where('store_id', $this->storeId)
            ->orWhereNull('store_id')
            ->get();
    }

    protected function getValidationRules($data, $model): array
    {
        return [
            'name' => 'required|max:191',
            'caption' => 'required',
            'status' => 'required|in:active,inactive',
            'type' => 'required|in:simple,variable',
            'inventory' => 'required_if:type,simple',
            'inventory_value' => 'required_if:inventory,finite,bucket',
            'regular_price' => 'required_if:type,simple',
            'code' => 'required_if:type,simple',
            'product_code' => 'required_if:type,variable',
            'shipping.width' => 'required_if:shippable,1',
            'shipping.height' => 'required_if:shippable,1',
            'shipping.length' => 'required_if:shippable,1',
            'shipping.weight' => 'required_if:shippable,1',
            'variation_options' => [
                'required_if:type,variable',
                function ($attribute, $value, $fail) use ($data) {
                    $set_attribute_options = data_get($data, 'set_attribute_options', []);
                    if (array_intersect($value, array_keys($set_attribute_options))) {
                        $fail($attribute . ' should be unique with product attributes');
                    }
                },
            ],
            'categories' => 'required',
        ];
    }

    protected function handleAttributeSets($record)
    {
        return [$this->attributeSet->id];
    }
}
