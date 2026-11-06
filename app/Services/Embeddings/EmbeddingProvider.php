<?php

namespace App\Services\Embeddings;

interface EmbeddingProvider
{
    /** @return array<int,float> */
    public function embed(string $text, string $inputType = 'document'): array;

    /**
     * @param  array<int,string>  $texts
     * @return array<int,array<int,float>> in the same order as $texts
     */
    public function embedMany(array $texts, string $inputType = 'document'): array;
}
