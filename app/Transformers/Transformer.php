<?php

namespace App\Transformers;

use Illuminate\Contracts\Support\Arrayable;

class Transformer extends BaseTransformer
{
    public function transform($data)
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }
        return (array)$data;
    }
}