<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

class GenresHasCategoriesRule implements Rule
{
    private $genresId;
    private $categoriesId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $categoriesId)
    {
        $this->categoriesId = array_unique($categoriesId);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_array($value)) {
            $value = [];
        }

        $this->genresId = array_unique($value);
        if (!count($this->genresId) || !count($this->categoriesId)) {
            return false;
        }
        $categoriesIdFound = [];
        foreach ($this->genresId as $genreId) {
            $categoriesRelationRows = $this->getCategoriesRelationRows($genreId);
            if (!$categoriesRelationRows->count()) {
                return false;
            }
            array_push($categoriesIdFound, ...$categoriesRelationRows->pluck('category_id')->toArray());
        }
        $categoriesIdFound = array_unique($categoriesIdFound);
        if (count($categoriesIdFound) !== count($this->categoriesId)) {
            return false;
        }
        return true;
    }

    protected function getCategoriesRelationRows($genreId): Collection
    {
        return \DB::table('category_genre')
            ->where('genre_id', $genreId)
            ->whereIn('category_id', $this->categoriesId)
            ->get();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The genre ID must be related with at least a category ID';
    }
}
