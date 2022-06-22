<?php

namespace Tests\Feature\Models\Video;

use App\Models\Video;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Http\UploadedFile;
use Tests\Exceptions\TestException;

class VideoUploadTest extends BaseVideoTestCase
{
    public function testCreateWithFiles()
    {
        \Storage::fake();
        $video = Video::create(
            $this->data + [
                'thumb_file' => UploadedFile::fake()->image('thump.jpg'),
                'video_file' => UploadedFile::fake()->create('video.mp4'),
                'trailer_file' => UploadedFile::fake()->image('trailer.mp4'),
                'banner_file' => UploadedFile::fake()->create('banner.jpg'),
            ]
        );
        \Storage::assertExists("{$video->getKey()}/{$video->thumb_file}");
        \Storage::assertExists("{$video->getKey()}/{$video->video_file}");
        \Storage::assertExists("{$video->getKey()}/{$video->trailer_file}");
        \Storage::assertExists("{$video->getKey()}/{$video->banner_file}");
    }

    public function testUpdateWithFiles()
    {
        \Storage::fake();
        $video = factory(Video::class)->create();
        $thumbFile = UploadedFile::fake()->image('thumb.jpg');
        $videoFile = UploadedFile::fake()->create('video.mp4');
        $banner_file = UploadedFile::fake()->image('thumb.jpg');
        $trailer_file = UploadedFile::fake()->create('video.mp4');
        $video->update($this->data + [
            'thumb_file' => $thumbFile,
            'video_file' => $videoFile,
            'banner_file' => $banner_file,
            'trailer_file' => $trailer_file,
        ]);
        \Storage::assertExists("{$video->getKey()}/{$video->thumb_file}");
        \Storage::assertExists("{$video->getKey()}/{$video->video_file}");
        \Storage::assertExists("{$video->getKey()}/{$video->banner_file}");
        \Storage::assertExists("{$video->getKey()}/{$video->trailer_file}");

        $newVideoFile = UploadedFile::fake()->create('video.mp4');
        $video->update($this->data + [
            'video_file' => $newVideoFile,
        ]);
        \Storage::assertExists("{$video->getKey()}/{$thumbFile->hashName()}");
        \Storage::assertExists("{$video->getKey()}/{$newVideoFile->hashName()}");
        \Storage::assertMissing("{$video->getKey()}/{$videoFile->hashName()}");
    }

    public function testCreateIfRollBackFiles()
    {
        \Storage::fake();
        \Event::listen(TransactionCommitted::class, function () {
            throw new TestException();
        });
        $hasError = false;

        try {
            Video::create(
                $this->data + [
                    'thumb_file' => UploadedFile::fake()->image('thump.jpg'),
                    'video_file' => UploadedFile::fake()->create('video.mp4'),
                    'trailer_file' => UploadedFile::fake()->image('trailer.mp4'),
                    'banner_file' => UploadedFile::fake()->create('banner.jpg'),
                ]
            );
        } catch (TestException $e) {
            $this->assertCount(0, \Storage::allFiles());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    public function testUpdateIfRollBackFiles()
    {
        \Storage::fake();
        $video = factory(Video::class)->create();
        \Event::listen(TransactionCommitted::class, function () {
            throw new TestException();
        });
        $hasError = false;

        try {
            $video->update(
                $this->data + [
                    'thumb_file' => UploadedFile::fake()->image('thump.jpg'),
                    'video_file' => UploadedFile::fake()->create('video.mp4'),
                    'trailer_file' => UploadedFile::fake()->image('trailer.mp4'),
                    'banner_file' => UploadedFile::fake()->create('banner.jpg'),
                ]
            );
        } catch (TestException $e) {
            $this->assertCount(0, \Storage::allFiles());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }
}
