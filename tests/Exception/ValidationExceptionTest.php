<?php

declare(strict_types=1);

namespace XGate\Tests\Exception;

use PHPUnit\Framework\TestCase;
use XGate\Exception\ValidationException;
use XGate\Exception\XGateException;

/**
 * Testes para ValidationException
 *
 * @package XGate\Tests\Exception
 */
class ValidationExceptionTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $exception = new ValidationException();

        $this->assertInstanceOf(XGateException::class, $exception);
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertEmpty($exception->getValidationErrors());
    }

    public function testConstructionWithMessage(): void
    {
        $message = 'Custom validation error';
        $exception = new ValidationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    public function testConstructionWithValidationErrors(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email format is invalid'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Multiple validation errors', $errors);

        $this->assertEquals($errors, $exception->getValidationErrors());
        $this->assertEquals('Multiple validation errors', $exception->getMessage());
    }

    public function testGenerateMessageFromErrors(): void
    {
        $errors = [
            'email' => ['Email is required']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertEquals("Validation failed for field 'email': Email is required", $exception->getMessage());
    }

    public function testGenerateMessageFromMultipleErrors(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email format is invalid'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertEquals('Validation failed for 2 field(s) with 3 error(s)', $exception->getMessage());
    }

    public function testConstructionWithFailedField(): void
    {
        $exception = new ValidationException(
            'Field validation failed',
            ['amount' => ['Must be positive']],
            'amount',
            -100,
            'positive'
        );

        $this->assertEquals('amount', $exception->getFailedField());
        $this->assertEquals(-100, $exception->getFailedValue());
        $this->assertEquals('positive', $exception->getFailedRule());
    }

    public function testGetFieldErrors(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email format is invalid'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertEquals(['Email is required', 'Email format is invalid'], $exception->getFieldErrors('email'));
        $this->assertEquals(['Amount must be positive'], $exception->getFieldErrors('amount'));
        $this->assertEquals([], $exception->getFieldErrors('nonexistent'));
    }

    public function testGetFirstFieldError(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email format is invalid'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertEquals('Email is required', $exception->getFirstFieldError('email'));
        $this->assertEquals('Amount must be positive', $exception->getFirstFieldError('amount'));
        $this->assertNull($exception->getFirstFieldError('nonexistent'));
    }

    public function testGetAllErrors(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email format is invalid'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);

        $allErrors = $exception->getAllErrors();
        $this->assertCount(3, $allErrors);
        $this->assertContains('Email is required', $allErrors);
        $this->assertContains('Email format is invalid', $allErrors);
        $this->assertContains('Amount must be positive', $allErrors);
    }

    public function testHasFieldError(): void
    {
        $errors = [
            'email' => ['Email is required'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertTrue($exception->hasFieldError('email'));
        $this->assertTrue($exception->hasFieldError('amount'));
        $this->assertFalse($exception->hasFieldError('nonexistent'));
    }

    public function testAddFieldError(): void
    {
        $exception = new ValidationException();
        
        $exception->addFieldError('email', 'Email is required');
        $exception->addFieldError('email', 'Email format is invalid');
        $exception->addFieldError('amount', 'Amount must be positive');

        $this->assertTrue($exception->hasFieldError('email'));
        $this->assertTrue($exception->hasFieldError('amount'));
        $this->assertEquals(['Email is required', 'Email format is invalid'], $exception->getFieldErrors('email'));
        $this->assertEquals(['Amount must be positive'], $exception->getFieldErrors('amount'));
    }

    public function testIsRequiredFieldError(): void
    {
        $exception1 = new ValidationException('Test', [], 'email', null, 'required');
        $this->assertTrue($exception1->isRequiredFieldError());

        $exception2 = new ValidationException('Field is required');
        $this->assertTrue($exception2->isRequiredFieldError());

        $exception3 = new ValidationException('Campo obrigatório');
        $this->assertTrue($exception3->isRequiredFieldError());

        $exception4 = new ValidationException('Invalid format');
        $this->assertFalse($exception4->isRequiredFieldError());
    }

    public function testIsFormatError(): void
    {
        $exception1 = new ValidationException('Test', [], 'email', null, 'format');
        $this->assertTrue($exception1->isFormatError());

        $exception2 = new ValidationException('Invalid format');
        $this->assertTrue($exception2->isFormatError());

        $exception3 = new ValidationException('Formato inválido');
        $this->assertTrue($exception3->isFormatError());

        $exception4 = new ValidationException('Field is required');
        $this->assertFalse($exception4->isFormatError());
    }

    public function testIsTypeError(): void
    {
        $exception1 = new ValidationException('Test', [], 'amount', null, 'numeric');
        $this->assertTrue($exception1->isTypeError());

        $exception2 = new ValidationException('Invalid type');
        $this->assertTrue($exception2->isTypeError());

        $exception3 = new ValidationException('Tipo inválido');
        $this->assertTrue($exception3->isTypeError());

        $exception4 = new ValidationException('Field is required');
        $this->assertFalse($exception4->isTypeError());
    }

    public function testMaskSensitiveValue(): void
    {
        $exception = new ValidationException(
            'Password validation failed',
            ['password' => ['Password too short']],
            'password',
            'secret123',
            'minlength'
        );

        $maskedValue = $exception->getFailedValue();
        $this->assertEquals('********', $maskedValue);
    }

    public function testMaskSensitiveValueWithToken(): void
    {
        $exception = new ValidationException(
            'Token validation failed',
            ['api_token' => ['Token invalid']],
            'api_token',
            'abc123xyz789',
            'format'
        );

        $maskedValue = $exception->getFailedValue();
        $this->assertEquals('********', $maskedValue);
    }

    public function testNonSensitiveValueNotMasked(): void
    {
        $exception = new ValidationException(
            'Email validation failed',
            ['email' => ['Email invalid']],
            'email',
            'test@example.com',
            'format'
        );

        $maskedValue = $exception->getFailedValue();
        $this->assertEquals('test@example.com', $maskedValue);
    }

    public function testNonStringValueNotMasked(): void
    {
        $exception = new ValidationException(
            'Amount validation failed',
            ['amount' => ['Amount must be positive']],
            'amount',
            -100,
            'positive'
        );

        $maskedValue = $exception->getFailedValue();
        $this->assertEquals(-100, $maskedValue);
    }

    public function testToArray(): void
    {
        $errors = [
            'email' => ['Email is required'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException(
            'Validation failed',
            $errors,
            'email',
            'invalid-email',
            'required'
        );

        $array = $exception->toArray();

        $this->assertArrayHasKey('validation_errors', $array);
        $this->assertArrayHasKey('failed_field', $array);
        $this->assertArrayHasKey('failed_value', $array);
        $this->assertArrayHasKey('failed_rule', $array);
        $this->assertArrayHasKey('error_count', $array);
        $this->assertArrayHasKey('field_count', $array);

        $this->assertEquals($errors, $array['validation_errors']);
        $this->assertEquals('email', $array['failed_field']);
        $this->assertEquals('invalid-email', $array['failed_value']);
        $this->assertEquals('required', $array['failed_rule']);
        $this->assertEquals(2, $array['error_count']);
        $this->assertEquals(2, $array['field_count']);
    }

    public function testToString(): void
    {
        $errors = [
            'email' => ['Email is required'],
            'amount' => ['Amount must be positive']
        ];
        
        $exception = new ValidationException('Validation failed', $errors);
        $string = (string) $exception;

        $this->assertStringContainsString('Validation failed', $string);
        $this->assertStringContainsString('Validation Errors:', $string);
        $this->assertStringContainsString('email: Email is required', $string);
        $this->assertStringContainsString('amount: Amount must be positive', $string);
    }

    public function testRequiredStaticMethod(): void
    {
        $exception = ValidationException::required('email', 'invalid-email');

        $this->assertEquals("The field 'email' is required", $exception->getMessage());
        $this->assertEquals(['email' => ['This field is required']], $exception->getValidationErrors());
        $this->assertEquals('email', $exception->getFailedField());
        $this->assertEquals('invalid-email', $exception->getFailedValue());
        $this->assertEquals('required', $exception->getFailedRule());
        $this->assertTrue($exception->isRequiredFieldError());
    }

    public function testInvalidFormatStaticMethod(): void
    {
        $exception = ValidationException::invalidFormat('email', 'invalid-email', 'valid email address');

        $this->assertEquals("The field 'email' has invalid format. Expected: valid email address", $exception->getMessage());
        $this->assertEquals(['email' => ['Invalid format. Expected: valid email address']], $exception->getValidationErrors());
        $this->assertEquals('email', $exception->getFailedField());
        $this->assertEquals('invalid-email', $exception->getFailedValue());
        $this->assertEquals('format', $exception->getFailedRule());
        $this->assertTrue($exception->isFormatError());
    }

    public function testInvalidTypeStaticMethod(): void
    {
        $exception = ValidationException::invalidType('amount', 'not-a-number', 'numeric');

        $this->assertEquals("The field 'amount' must be of type numeric, string given", $exception->getMessage());
        $this->assertEquals(['amount' => ['Must be of type numeric, string given']], $exception->getValidationErrors());
        $this->assertEquals('amount', $exception->getFailedField());
        $this->assertEquals('not-a-number', $exception->getFailedValue());
        $this->assertEquals('type', $exception->getFailedRule());
        $this->assertTrue($exception->isTypeError());
    }

    public function testContextIsSetCorrectly(): void
    {
        $errors = ['email' => ['Email is required']];
        $exception = new ValidationException('Test', $errors, 'email', 'test@example.com', 'required');

        $context = $exception->getContext();
        
        $this->assertArrayHasKey('validation_errors', $context);
        $this->assertArrayHasKey('failed_field', $context);
        $this->assertArrayHasKey('failed_value', $context);
        $this->assertArrayHasKey('failed_rule', $context);

        $this->assertEquals($errors, $context['validation_errors']);
        $this->assertEquals('email', $context['failed_field']);
        $this->assertEquals('test@example.com', $context['failed_value']);
        $this->assertEquals('required', $context['failed_rule']);
    }

    public function testContextUpdatedWhenAddingFieldError(): void
    {
        $exception = new ValidationException();
        $exception->addFieldError('email', 'Email is required');

        $context = $exception->getContext();
        $this->assertEquals(['email' => ['Email is required']], $context['validation_errors']);
    }
} 