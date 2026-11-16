<?php

/**
 * HTTP client for tests - sends nothing, only records requests and returns prepared responses.
 */
final class FakeHttpClient implements BalikobotHttpClient {

    /** @var array{url: string, body: string|null, headers: string[]}[] */
    public array $calls = [];

    /** @var array{body: string, httpStatus: int, error: string}[] */
    private array $queuedResponses = [];

    /** @var array{body: string, httpStatus: int, error: string} */
    private array $defaultResponse;

    public function __construct(string $body = '{"status":200}', int $httpStatus = 200, string $error = '') {
        $this->defaultResponse = ['body' => $body, 'httpStatus' => $httpStatus, 'error' => $error];
    }

    /**
     * Several responses can be queued, they are returned in the order they were queued.
     */
    public function queueResponse(string $body, int $httpStatus = 200, string $error = ''): self {
        $this->queuedResponses[] = ['body' => $body, 'httpStatus' => $httpStatus, 'error' => $error];

        return $this;
    }

    /**
     * @param string[] $headers
     * @return array{body: string, httpStatus: int, error: string}
     */
    public function post(string $url, ?string $jsonBody, array $headers): array {
        $this->calls[] = ['url' => $url, 'body' => $jsonBody, 'headers' => $headers];

        return array_shift($this->queuedResponses) ?? $this->defaultResponse;
    }

    public function callCount(): int {
        return count($this->calls);
    }

    public function lastUrl(): string {
        return $this->lastCall()['url'];
    }

    public function lastBody(): ?string {
        return $this->lastCall()['body'];
    }

    /**
     * Decoded body, so it can be compared regardless of JSON formatting.
     *
     * @return mixed[]|null
     */
    public function lastDecodedBody(): ?array {
        $body = $this->lastBody();
        if ($body === null) {
            return null;
        }

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[]
     */
    public function lastHeaders(): array {
        return $this->lastCall()['headers'];
    }

    /**
     * @return array{url: string, body: string|null, headers: string[]}
     */
    private function lastCall(): array {
        if ($this->calls === []) {
            throw new LogicException('No request has been sent.');
        }

        return $this->calls[count($this->calls) - 1];
    }

}
