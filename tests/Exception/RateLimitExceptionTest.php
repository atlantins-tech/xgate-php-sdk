<?php

declare(strict_types=1);

namespace XGate\Tests\Exception;

use PHPUnit\Framework\TestCase;
use XGate\Exception\RateLimitException;
use XGate\Exception\ApiException;
use XGate\Exception\XGateException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Testes para RateLimitException
 *
 * @package XGate\Tests\Exception
 */
class RateLimitExceptionTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $exception = new RateLimitException();

        $this->assertInstanceOf(XGateException::class, $exception);
        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(RateLimitException::class, $exception);
        $this->assertEquals('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testConstructionWithMessage(): void
    {
        $message = 'Custom rate limit message';
        $exception = new RateLimitException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testConstructionWithRetryAfter(): void
    {
        $retryAfter = 60;
        $exception = new RateLimitException('Rate limit exceeded', $retryAfter);

        $this->assertEquals($retryAfter, $exception->getRetryAfter());
        $this->assertTrue($exception->hasRetryAfter());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testConstructionWithRateLimitInfo(): void
    {
        $info = [
            'retry_after' => 30,
            'rate_limit' => 100,
            'rate_limit_remaining' => 0,
            'rate_limit_reset' => time() + 3600,
            'limit_type' => 'hourly',
            'client_id' => 'client-123'
        ];

        $exception = new RateLimitException('Rate limit exceeded', $info);

        $this->assertEquals(30, $exception->getRetryAfter());
        $this->assertEquals(100, $exception->getRateLimit());
        $this->assertEquals(0, $exception->getRateLimitRemaining());
        $this->assertEquals($info['rate_limit_reset'], $exception->getRateLimitReset());
        $this->assertEquals('hourly', $exception->getLimitType());
        $this->assertEquals('client-123', $exception->getClientId());
    }

    public function testConstructionWithHttpObjects(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->method('getStatusCode')->willReturn(429);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn('{"error": "Rate limit exceeded"}');

        // Configure headers
        $response->method('hasHeader')->willReturnCallback(function ($header) {
            return in_array($header, ['Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining']);
        });
        
        $response->method('getHeaderLine')->willReturnCallback(function ($header) {
            switch ($header) {
                case 'Retry-After':
                    return '60';
                case 'X-RateLimit-Limit':
                    return '100';
                case 'X-RateLimit-Remaining':
                    return '0';
                default:
                    return '';
            }
        });

        $exception = new RateLimitException('Rate limit exceeded', $request, $response);

        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertEquals(100, $exception->getRateLimit());
        $this->assertEquals(0, $exception->getRateLimitRemaining());
    }

    public function testGenerateInformativeMessage(): void
    {
        $exception = new RateLimitException('Rate limit exceeded', 30);
        $this->assertStringContainsString('Retry after 30 seconds', $exception->getMessage());
    }

    public function testGenerateInformativeMessageWithUsage(): void
    {
        $info = [
            'rate_limit' => 100,
            'rate_limit_remaining' => 20,
            'limit_type' => 'hourly'
        ];

        $exception = new RateLimitException('Rate limit exceeded', $info);
        $message = $exception->getMessage();

        $this->assertStringContainsString('(80/100 requests used)', $message);
        $this->assertStringContainsString('[Type: hourly]', $message);
    }

    public function testGenerateInformativeMessageWithReset(): void
    {
        $resetTime = time() + 300; // 5 minutes from now
        $info = [
            'rate_limit_reset' => $resetTime
        ];

        $exception = new RateLimitException('Rate limit exceeded', $info);
        $message = $exception->getMessage();

        $this->assertStringContainsString('Limit resets in 300 seconds', $message);
    }

    public function testHasRetryAfter(): void
    {
        $exception1 = new RateLimitException('Test', 60);
        $this->assertTrue($exception1->hasRetryAfter());

        $exception2 = new RateLimitException('Test', 0);
        $this->assertFalse($exception2->hasRetryAfter());

        $exception3 = new RateLimitException('Test');
        $this->assertFalse($exception3->hasRetryAfter());
    }

    public function testIsLimitReset(): void
    {
        $pastTime = time() - 100;
        $futureTime = time() + 100;

        $exception1 = new RateLimitException('Test', ['rate_limit_reset' => $pastTime]);
        $this->assertTrue($exception1->isLimitReset());

        $exception2 = new RateLimitException('Test', ['rate_limit_reset' => $futureTime]);
        $this->assertFalse($exception2->isLimitReset());

        $exception3 = new RateLimitException('Test');
        $this->assertFalse($exception3->isLimitReset());
    }

    public function testGetSecondsUntilReset(): void
    {
        $futureTime = time() + 300;
        $exception = new RateLimitException('Test', ['rate_limit_reset' => $futureTime]);

        $secondsLeft = $exception->getSecondsUntilReset();
        $this->assertGreaterThanOrEqual(290, $secondsLeft);
        $this->assertLessThanOrEqual(300, $secondsLeft);
    }

    public function testGetSecondsUntilResetPast(): void
    {
        $pastTime = time() - 100;
        $exception = new RateLimitException('Test', ['rate_limit_reset' => $pastTime]);

        $this->assertEquals(0, $exception->getSecondsUntilReset());
    }

    public function testGetSecondsUntilResetNoInfo(): void
    {
        $exception = new RateLimitException('Test');
        $this->assertEquals(0, $exception->getSecondsUntilReset());
    }

    public function testIsFullyExhausted(): void
    {
        $exception1 = new RateLimitException('Test', ['rate_limit_remaining' => 0]);
        $this->assertTrue($exception1->isFullyExhausted());

        $exception2 = new RateLimitException('Test', ['rate_limit_remaining' => 5]);
        $this->assertFalse($exception2->isFullyExhausted());

        $exception3 = new RateLimitException('Test');
        $this->assertFalse($exception3->isFullyExhausted());
    }

    public function testGetLimitUsagePercentage(): void
    {
        $info = [
            'rate_limit' => 100,
            'rate_limit_remaining' => 20
        ];
        $exception = new RateLimitException('Test', $info);

        $this->assertEquals(80.0, $exception->getLimitUsagePercentage());
    }

    public function testGetLimitUsagePercentageFullyUsed(): void
    {
        $info = [
            'rate_limit' => 100,
            'rate_limit_remaining' => 0
        ];
        $exception = new RateLimitException('Test', $info);

        $this->assertEquals(100.0, $exception->getLimitUsagePercentage());
    }

    public function testGetLimitUsagePercentageZeroLimit(): void
    {
        $info = [
            'rate_limit' => 0,
            'rate_limit_remaining' => 0
        ];
        $exception = new RateLimitException('Test', $info);

        $this->assertEquals(100.0, $exception->getLimitUsagePercentage());
    }

    public function testGetLimitUsagePercentageNoInfo(): void
    {
        $exception = new RateLimitException('Test');
        $this->assertNull($exception->getLimitUsagePercentage());
    }

    public function testGetRateLimitResetDateTime(): void
    {
        $timestamp = 1640995200; // 2022-01-01 00:00:00 UTC
        $exception = new RateLimitException('Test', ['rate_limit_reset' => $timestamp]);

        $dateTime = $exception->getRateLimitResetDateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertEquals($timestamp, $dateTime->getTimestamp());
    }

    public function testGetRateLimitResetDateTimeNoInfo(): void
    {
        $exception = new RateLimitException('Test');
        $this->assertNull($exception->getRateLimitResetDateTime());
    }

    public function testSetRateLimitInfo(): void
    {
        $exception = new RateLimitException('Test');
        
        $info = [
            'retry_after' => 30,
            'rate_limit' => 100,
            'rate_limit_remaining' => 10,
            'rate_limit_reset' => time() + 3600,
            'limit_type' => 'hourly',
            'client_id' => 'client-123'
        ];

        $result = $exception->setRateLimitInfo($info);

        $this->assertSame($exception, $result); // Test fluent interface
        $this->assertEquals(30, $exception->getRetryAfter());
        $this->assertEquals(100, $exception->getRateLimit());
        $this->assertEquals(10, $exception->getRateLimitRemaining());
        $this->assertEquals($info['rate_limit_reset'], $exception->getRateLimitReset());
        $this->assertEquals('hourly', $exception->getLimitType());
        $this->assertEquals('client-123', $exception->getClientId());
    }

    public function testToArray(): void
    {
        $resetTime = time() + 3600;
        $info = [
            'retry_after' => 30,
            'rate_limit' => 100,
            'rate_limit_remaining' => 20,
            'rate_limit_reset' => $resetTime,
            'limit_type' => 'hourly',
            'client_id' => 'client-123'
        ];

        $exception = new RateLimitException('Test', $info);
        $array = $exception->toArray();

        $this->assertArrayHasKey('retry_after', $array);
        $this->assertArrayHasKey('rate_limit', $array);
        $this->assertArrayHasKey('rate_limit_remaining', $array);
        $this->assertArrayHasKey('rate_limit_reset', $array);
        $this->assertArrayHasKey('rate_limit_reset_datetime', $array);
        $this->assertArrayHasKey('limit_type', $array);
        $this->assertArrayHasKey('client_id', $array);
        $this->assertArrayHasKey('seconds_until_reset', $array);
        $this->assertArrayHasKey('is_fully_exhausted', $array);
        $this->assertArrayHasKey('usage_percentage', $array);

        $this->assertEquals(30, $array['retry_after']);
        $this->assertEquals(100, $array['rate_limit']);
        $this->assertEquals(20, $array['rate_limit_remaining']);
        $this->assertEquals($resetTime, $array['rate_limit_reset']);
        $this->assertEquals('hourly', $array['limit_type']);
        $this->assertEquals('client-123', $array['client_id']);
        $this->assertFalse($array['is_fully_exhausted']);
        $this->assertEquals(80.0, $array['usage_percentage']);
    }

    public function testToString(): void
    {
        $resetTime = time() + 3600;
        $info = [
            'retry_after' => 30,
            'rate_limit' => 100,
            'rate_limit_remaining' => 20,
            'rate_limit_reset' => $resetTime,
            'limit_type' => 'hourly'
        ];

        $exception = new RateLimitException('Test', $info);
        $string = (string) $exception;

        $this->assertStringContainsString('Test', $string);
        $this->assertStringContainsString('Rate Limit Information:', $string);
        $this->assertStringContainsString('Retry After: 30 seconds', $string);
        $this->assertStringContainsString('Rate Limit: 100 requests', $string);
        $this->assertStringContainsString('Remaining: 20 requests', $string);
        $this->assertStringContainsString('Limit Type: hourly', $string);
        $this->assertStringContainsString('Usage: 80.0%', $string);
    }

    public function testWithRetryAfterStaticMethod(): void
    {
        $exception = RateLimitException::withRetryAfter(60);

        $this->assertEquals('Rate limit exceeded. Retry after 60 seconds', $exception->getMessage());
        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testWithRetryAfterStaticMethodCustomMessage(): void
    {
        $message = 'Custom message';
        $exception = RateLimitException::withRetryAfter(30, $message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(30, $exception->getRetryAfter());
    }

    public function testWithLimitInfoStaticMethod(): void
    {
        $resetTime = time() + 3600;
        $exception = RateLimitException::withLimitInfo(100, 10, $resetTime, 'hourly');

        $this->assertEquals(100, $exception->getRateLimit());
        $this->assertEquals(10, $exception->getRateLimitRemaining());
        $this->assertEquals($resetTime, $exception->getRateLimitReset());
        $this->assertEquals('hourly', $exception->getLimitType());
        // A mensagem será gerada automaticamente com informações detalhadas
        $this->assertStringContainsString('Rate limit exceeded', $exception->getMessage());
    }

    public function testWithLimitInfoStaticMethodCustomMessage(): void
    {
        $message = 'Custom limit message';
        $resetTime = time() + 3600;
        $exception = RateLimitException::withLimitInfo(100, 0, $resetTime, 'daily', $message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(100, $exception->getRateLimit());
        $this->assertEquals(0, $exception->getRateLimitRemaining());
        $this->assertTrue($exception->isFullyExhausted());
    }

    public function testContextIsSetCorrectly(): void
    {
        $info = [
            'retry_after' => 30,
            'rate_limit' => 100,
            'rate_limit_remaining' => 20,
            'rate_limit_reset' => time() + 3600,
            'limit_type' => 'hourly',
            'client_id' => 'client-123'
        ];

        $exception = new RateLimitException('Test', $info);
        $context = $exception->getContext();

        $this->assertArrayHasKey('rate_limit_info', $context);
        $rateLimitInfo = $context['rate_limit_info'];

        $this->assertEquals(30, $rateLimitInfo['retry_after']);
        $this->assertEquals(100, $rateLimitInfo['rate_limit']);
        $this->assertEquals(20, $rateLimitInfo['rate_limit_remaining']);
        $this->assertEquals($info['rate_limit_reset'], $rateLimitInfo['rate_limit_reset']);
        $this->assertEquals('hourly', $rateLimitInfo['limit_type']);
        $this->assertEquals('client-123', $rateLimitInfo['client_id']);
    }

    public function testExtractRateLimitInfoFromResponse(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->method('getStatusCode')->willReturn(429);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn('{"retry_after": 45, "rate_limit": 200}');

        // Configure different header formats
        $response->method('hasHeader')->willReturnCallback(function ($header) {
            return in_array($header, [
                'X-Retry-After', 
                'X-Rate-Limit-Limit', 
                'X-Rate-Limit-Remaining',
                'X-Rate-Limit-Type',
                'X-Rate-Limit-Client-ID'
            ]);
        });
        
        $response->method('getHeaderLine')->willReturnCallback(function ($header) {
            switch ($header) {
                case 'X-Retry-After':
                    return '60';
                case 'X-Rate-Limit-Limit':
                    return '150';
                case 'X-Rate-Limit-Remaining':
                    return '5';
                case 'X-Rate-Limit-Type':
                    return 'daily';
                case 'X-Rate-Limit-Client-ID':
                    return 'test-client';
                default:
                    return '';
            }
        });

        $exception = new RateLimitException('Rate limit exceeded', $request, $response);

        // Headers should take precedence over response body, but response body can provide fallback values
        // The actual values will be from headers (60, 150, 5) not from response body (45, 200)
        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertEquals(150, $exception->getRateLimit());
        $this->assertEquals(5, $exception->getRateLimitRemaining());
        $this->assertEquals('daily', $exception->getLimitType());
        $this->assertEquals('test-client', $exception->getClientId());
    }
} 