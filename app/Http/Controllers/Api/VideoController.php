<?php

namespace App\Http\Controllers\Api;

use App\Models\Video;
use App\Rules\GenresHasCategoriesRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VideoController extends BasicCrudController
{
    private $rules;

    public function __construct()
    {
        $this->rules = [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_release' => 'required|date_format:Y',
            'opened' => 'boolean',
            'rating' => ['required', Rule::in(Video::RATING_LIST)],
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
            'genres_id' => [
                'required',
                'array',
                'exists:genres,id,deleted_at,NULL'
            ],
            'video_file' => 'nullable|file|mimetypes:video/mp4|max:52428800', // 50GB -> 52428800KB
            'thumb_file' => 'nullable|file|mimetypes:image/jpeg|max:5120',    // 5MB  -> 5120KB
            'trailer_file' => 'nullable|file|mimetypes:video/mp4|max:1048576',   // 1GB  -> 1048576KB
            'banner_file' => 'nullable|file|mimetypes:image/jpeg|max:10240',   // 10MB -> 10240KB
        ];
    }

    public function store(Request $request)
    {
        $this->addRuleGenreHasCategories($request);
        $validatedData = $this->validate($request, $this->rulesStore());
        $obj = $this->model()::create($validatedData);
        $obj->refresh();
        return $obj;
    }

    public function update(Request $request, $id)
    {
        $obj = $this->findOrFail($id);
        $this->addRuleGenreHasCategories($request);
        $validatedData = $this->validate($request, $this->rulesUpdate());
        $obj->update($validatedData);
        return $obj;
    }

    protected function addRuleGenreHasCategories(Request $request)
    {
        $categoriesId = $request->get('categories_id');
        $categoriesId = is_array($categoriesId) ? $categoriesId : [];
        $this->rules['genres_id'][] = new GenresHasCategoriesRule($categoriesId);
    }

    protected function model()
    {
        return Video::class;
    }

    protected function rulesStore()
    {
        return $this->rules;
    }

    protected function rulesUpdate()
    {
        return $this->rules;
    }
}
