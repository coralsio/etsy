@extends('layouts.master')

@section('title',strip_tags(trans('Etsy::attributes.import_product.import')))
@section('content')
    {!! Form::open(['url'=>'marketplace/etsy/do-import-products','files'=>true,'class'=>'ajax-form','data-page_action'=>'site_reload']) !!}
    <div class="row">
        <div class="col-md-4">

            @component('components.box')
                <div class="row">

                    <div class="col-md-12">
                        {!! CoralsForm::file('file', 'Etsy::attributes.import_product.file_path', true,['multiple' => false]) !!}
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-12">
                        {!! CoralsForm::checkbox('clear_existing_images', 'Etsy::attributes.import_product.clear_existing_images') !!}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        {!! \CoralsForm::select('store_id', 'Etsy::attributes.import_product.store', [], true, null,
                               [
                                   'class' => 'select2-ajax',
                                   'data' => [
                                       'model' => \Corals\Modules\Marketplace\Models\Store::class,
                                       'columns' => json_encode(['name']),
                                       'selected' => json_encode( []),
                                        'where' => json_encode([]),
                                   ]
                               ], 'select2'); !!}
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-12">
                        {!! CoralsForm::formButtons('Etsy::attributes.import_product.import',[],['show_cancel'=>false]) !!}
                    </div>
                </div>

            @endcomponent
        </div>

    </div>
    {!! Form::close() !!}
@endsection