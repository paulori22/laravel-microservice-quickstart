<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\GenreController;
use App\Http\Resources\GenreResource;
use App\Models\Category;
use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;
use Tests\TestCase;
use Tests\Traits\TestResources;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidations;

class GenreControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations, TestSaves, TestResources;

    private $genre;
    private $serializedFields = [
        'id',
        'name',
        'is_active',
        'categories' => [
            '*' =>  [
                'id',
                'name',
                'description',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at'
            ]
        ],
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->genre = factory(Genre::class)->create();
    }

    public function testIndex()
    {
        $response = $this->get(route('genres.index'));
        $response
            ->assertStatus(200)
            ->assertJson([
                'meta' => ['per_page' => 15]
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => $this->serializedFields
                ],
                'links' => [],
                'meta' => [],
            ]);

        $resource = GenreResource::collection(collect([$this->genre]));
        $this->assertResource($response, $resource);
    }

    public function testShow()
    {
        $response = $this->get(route('genres.show', ['genre' => $this->genre->getKey()]));

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => $this->serializedFields
            ]);

        $id = $this->getIdFromResponse($response);
        $resource = new GenreResource(Genre::find($id));
        $this->assertResource($response, $resource);
    }

    public function testInvalidationData()
    {
        $data = [
            'name' => ''
        ];
        $this->assertInvalidationInStoreAction($data, 'required');
        $this->assertInvalidationInUpdateAction($data, 'required');

        $data = [
            'name' => str_repeat('a', 256)
        ];
        $this->assertInvalidationInStoreAction($data, 'max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'max.string', ['max' => 255]);

        $data = [
            'is_active' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'boolean');
        $this->assertInvalidationInUpdateAction($data, 'boolean');
    }

    public function testInvalidationCategoriesIdField()
    {
        $data = [
            'categories_id' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'array');
        $this->assertInvalidationInUpdateAction($data, 'array');

        $data = [
            'categories_id' => [100]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');

        $category = factory(Category::class)->create();
        $category->delete();
        $data = [
            'categories_id' => [$category->getKey()]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');
    }

    public function testStore()
    {
        $category = factory(Category::class)->create();

        $data = [
            'name' => 'test',
        ];

        $response = $this->assertStore($data + [
            'categories_id' => [$category->getKey()]
        ], $data + ['is_active' => true, 'deleted_at' => null]);
        $response->assertJsonStructure([
            'data' => $this->serializedFields
        ]);
        $this->assertHasCategory($this->getIdFromResponse($response), $category->getKey());

        $id = $this->getIdFromResponse($response);
        $resource = new GenreResource(Genre::find($id));
        $this->assertResource($response, $resource);

        $data = [
            'name' => 'test',
            'is_active' => false,
        ];
        $response = $this->assertStore($data + [
            'categories_id' => [$category->getKey()]
        ], $data + ['is_active' => false]);
        $response->assertJsonStructure([
            'data' => $this->serializedFields
        ]);
        $this->assertHasCategory($this->getIdFromResponse($response), $category->getKey());
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $this->genre = factory(Genre::class)->create([
            'is_active' => false,
        ]);

        $data =        [
            'name' => 'test',
            'is_active' => true,
        ];

        $response = $this->assertUpdate($data + [
            'categories_id' => [$category->getKey()]
        ], $data + ['deleted_at' => null]);
        $response->assertJsonStructure([
            'data' => $this->serializedFields
        ]);
        $this->assertHasCategory($this->getIdFromResponse($response), $category->getKey());

        $id = $response->json('data.id');
        $resource = new GenreResource(Genre::find($id));
        $this->assertResource($response, $resource);
    }

    protected function assertHasCategory($genreId, $categoryId)
    {
        $this->assertDatabaseHas('category_genre', [
            'genre_id' => $genreId,
            'category_id' => $categoryId
        ]);
    }

    public function testRollBackStore()
    {
        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'name' => 'test'
            ]);

        $controller
            ->shouldReceive('rulesStore')
            ->withAnyArgs()
            ->andReturn([]);

        $controller
            ->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $request = \Mockery::mock(Request::class);

        $hasError = false;
        try {
            $controller->store($request);
        } catch (TestException $exception) {
            $this->assertCount(1, Genre::all());
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }

    public function testRollBackUpdate()
    {
        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller
            ->shouldReceive('findOrFail')
            ->withAnyArgs()
            ->andReturn($this->genre);

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'name' => 'test'
            ]);

        $controller
            ->shouldReceive('rulesUpdate')
            ->withAnyArgs()
            ->andReturn([]);

        $controller
            ->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $request = \Mockery::mock(Request::class);

        $hasError = false;
        try {
            $controller->update($request, 1);
        } catch (TestException $exception) {
            $this->assertCount(1, Genre::all());
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }

    public function testDelete()
    {
        $response = $this->json(
            'DELETE',
            route('genres.destroy', ['genre' => $this->genre->getKey()]),
            []
        );
        $response
            ->assertStatus(204);
        $this->assertNull(Genre::find($this->genre->getKey()));
        $this->assertNotNull(Genre::withTrashed()->find($this->genre->getKey()));
    }

    public function testSyncCategories()
    {
        $categoriesId = factory(Category::class, 3)->create()->pluck('id')->toArray();
        $sendData = [
            'name' => 'test',
            'categories_id' => [$categoriesId[0]]
        ];
        $response = $this->json('POST', $this->routeStore(), $sendData);
        $this->assertDatabaseHas('category_genre', [
            'category_id' => $categoriesId[0],
            'genre_id' => $this->getIdFromResponse($response)
        ]);

        $sendData = [
            'name' => 'test',
            'categories_id' => [$categoriesId[1], $categoriesId[2]]
        ];
        $response = $this->json(
            'PUT',
            route('genres.update', ['genre' => $this->getIdFromResponse($response)]),
            $sendData
        );
        $this->assertDatabaseMissing('category_genre', [
            'category_id' => $categoriesId[0],
            'genre_id' => $this->getIdFromResponse($response)
        ]);
        $this->assertDatabaseHas('category_genre', [
            'category_id' => $categoriesId[1],
            'genre_id' => $this->getIdFromResponse($response)
        ]);
        $this->assertDatabaseHas('category_genre', [
            'category_id' => $categoriesId[2],
            'genre_id' => $this->getIdFromResponse($response)
        ]);
    }

    protected function routeStore()
    {
        return route('genres.store');
    }

    protected function routeUpdate()
    {
        return route('genres.update', ['genre' => $this->genre->getKey()]);
    }

    protected function model()
    {
        return Genre::class;
    }
}
