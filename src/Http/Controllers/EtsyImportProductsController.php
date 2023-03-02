<?php

namespace Corals\Modules\Etsy\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\Etsy\Jobs\HandleProductsImportFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class EtsyImportProductsController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function import(Request $request)
    {
        return view('Etsy::import_products.index');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function doImport(Request $request)
    {
        $request->validate([
            'store_id' => 'required',
            'file' => 'required|mimes:csv,txt|max:' . maxUploadFileSize(),
        ]);

        try {
            $file = $request->file('file');
            $importsPath = storage_path('app/marketplace/imports');
            $fileName = sprintf("%s_%s", Str::random(), $file->getClientOriginalName());

            $fileFullPath = $importsPath . '/' . $fileName;
            $file->move($importsPath, $fileName);


            $clearExistingImages = $request->get('clear_existing_images');
            $storeId = $request->get('store_id');

            HandleProductsImportFile::dispatch($fileFullPath, $clearExistingImages, $storeId, user());

            $message = [
                'message' => trans('Etsy::messages.successfully_uploaded'),
                'level' => 'success'
            ];
        } catch (\Exception $exception) {
            $message = [
                'message' => $exception->getMessage(),
                'level' => 'error'
            ];

            report($exception);

            $code = 400;
        }

        return response()->json($message, $code ?? 200);
    }
}
