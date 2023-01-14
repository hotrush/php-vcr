<?php

declare(strict_types=1);

namespace VCR\Util;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use VCR\Configuration;

class Encrypter
{
    private ?Key $key;
    private bool $enabled;

    public function __construct(
        private Configuration $config,
    ) {
        $this->enabled = !empty($this->config->getEncryptionKey());
        $this->key = $this->enabled
            ? Key::loadFromAsciiSafeString($this->config->getEncryptionKey())
            : null;
    }

    public function encryptRequestData(array $request): array
    {
        if (!$this->enabled) {
            return $request;
        }

        foreach ($this->getEncryptableRequestKeys() as $requestKey => $sensitiveKeys) {
            if (empty($request[$requestKey]) || empty($sensitiveKeys)) {
                continue;
            }
            foreach ($request[$requestKey] as $key => $value) {
                if (!$this->keyMatches($key, $sensitiveKeys)) {
                    continue;
                }
                $request[$requestKey][$key] = $this->encryptValue($value);
            }
        }

        return $request;
    }

    public function decryptRequestData(array $request): array
    {
        if (!$this->enabled) {
            return $request;
        }

        foreach ($this->getEncryptableRequestKeys() as $requestKey => $sensitiveKeys) {
            if (empty($request[$requestKey]) || empty($sensitiveKeys)) {
                continue;
            }
            foreach ($request[$requestKey] as $key => $value) {
                if (!$this->keyMatches($key, $sensitiveKeys)) {
                    continue;
                }
                $request[$requestKey][$key] = $this->decryptValue($value);
            }
        }

        return $request;
    }

    private function getEncryptableRequestKeys(): array
    {
        return [
            'headers' => $this->config->getSensitiveHeaders(),
        ];
    }

    private function keyMatches(string $keyToMatch, array $keys): bool
    {
        foreach ($keys as $key) {
            if (strcasecmp($keyToMatch, $key) === 0) {
                return true;
            }
        }

        return false;
    }

    private function encryptValue(string $value): string
    {
        return Crypto::encrypt($value, $this->key);
    }

    private function decryptValue(string $value): string
    {
        return Crypto::decrypt($value, $this->key);
    }
}
