<?php

class Kudja_Tinify_Model_Source_Method
{

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'cwebp', 'label' => 'Local cwebp'],
            ['value' => 'tinify_api', 'label' => 'Tinify (TyniPNG) API'],
        ];
    }

}
