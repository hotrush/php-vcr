<?php

declare(strict_types=1);

namespace VCR\Tests\Unit;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use VCR\Cassette;
use VCR\Configuration;
use VCR\Request;
use VCR\Response;
use VCR\Storage\Yaml;
use VCR\Util\Encrypter;

final class CassetteTest extends TestCase
{
    private Cassette $cassette;

    protected function setUp(): void
    {
        vfsStream::setup('test');
        $this->cassette = new Cassette(
            'test',
            $config = (new Configuration())->setEncryptionKey(
                'def0000042b702390c1aeaebf8d3db04ee5fc38cf8390e47f23324812ca1f8295a9cfeb515fc58b0b0688f14291fd6fd4086b43b8976a5eeefac76ebdb15a8452cc2bf3c'
            ),
            new Yaml(vfsStream::url('test/'), 'json_test'),
            new Encrypter($config)
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('test', $this->cassette->getName());
    }

    public function testDontOverwriteRecord(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response1 = new Response('200', [], 'sometest');
        $response2 = new Response('200', [], 'sometest');
        $this->cassette->record($request, $response1);
        $this->cassette->record($request, $response2);

        $this->assertEquals($response1->toArray(), $this->cassette->playback($request)?->toArray());
    }

    public function testPlaybackAlreadyRecordedRequest(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response('200', [], 'sometest');
        $this->cassette->record($request, $response);

        $this->assertEquals($response->toArray(), $this->cassette->playback($request)?->toArray());
    }

    public function testPlaybackAlreadyRecordedRequestWithSensitiveHeader(): void
    {
        $request = new Request('GET', 'https://example.com', ['Authorization' => 'Bearer some-token']);
        $response = new Response('200', [], 'sometest');
        $this->cassette->record($request, $response);

        $this->assertEquals($response->toArray(), $this->cassette->playback($request)?->toArray());
    }

    public function testHasResponseNotFound(): void
    {
        $request = new Request('GET', 'https://example.com');

        $this->assertFalse($this->cassette->hasResponse($request), 'Expected false if request not found.');
    }

    public function testHasResponseFound(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response('200', [], 'sometest');
        $this->cassette->record($request, $response);

        $this->assertTrue($this->cassette->hasResponse($request), 'Expected true if request was found.');
    }
}
