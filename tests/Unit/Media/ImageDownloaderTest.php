<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Media;

use ContentPulse\Media\ImageDownloader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImageDownloaderTest extends TestCase
{
    #[Test]
    public function it_downloads_contentpulse_public_chart_paths_to_local_storage(): void
    {
        $directory = sys_get_temp_dir().'/contentpulse-sdk-'.bin2hex(random_bytes(4));
        $upstream = 'https://contentpulse.test/storage/content/42/charts/ai-adoption.png';
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'image/png'], str_repeat('P', 64)),
        ]))]);

        try {
            $downloader = new ImageDownloader(
                storagePath: $directory,
                publicUrlPrefix: '/storage/media/blog',
                httpClient: $client,
                sourceBaseUrl: 'https://contentpulse.test',
            );

            $result = $downloader->localize('/storage/content/42/charts/ai-adoption.png');
            $filename = sha1($upstream).'.png';

            $this->assertSame('/storage/media/blog/'.$filename, $result);
            $this->assertFileExists($directory.'/'.$filename);
        } finally {
            if (is_dir($directory)) {
                foreach (glob($directory.'/*') ?: [] as $file) {
                    unlink($file);
                }
                rmdir($directory);
            }
        }
    }
}
