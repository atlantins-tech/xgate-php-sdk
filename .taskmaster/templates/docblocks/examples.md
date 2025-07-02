# 📚 Examples Documentation Templates

This file contains standardized PHPDoc example templates for the XGATE PHP SDK documentation.

## 📋 Basic Example Template

```php
/**
 * @example Basic usage
 * ```php
 * // Brief description of what this example demonstrates
 * $instance = new ClassName($param1, $param2);
 * $result = $instance->method();
 * echo "Result: " . $result;
 * ```
 */
```

## 🎯 Specialized Example Templates

### API Resource Examples

#### Simple CRUD Operations
```php
/**
 * @example Basic CRUD operations
 * ```php
 * // Initialize the resource
 * $customerResource = new CustomerResource($httpClient, $logger);
 * 
 * // Create a new customer
 * $newCustomer = $customerResource->create([
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'document' => '12345678901'
 * ]);
 * echo "Created customer: " . $newCustomer->getId();
 * 
 * // Retrieve the customer
 * $customer = $customerResource->get($newCustomer->getId());
 * echo "Customer name: " . $customer->getName();
 * 
 * // Update the customer
 * $updated = $customerResource->update($customer->getId(), [
 *     'name' => 'John Smith'
 * ]);
 * echo "Updated name: " . $updated->getName();
 * 
 * // Delete the customer
 * $success = $customerResource->delete($customer->getId());
 * echo $success ? "Customer deleted" : "Delete failed";
 * ```
 */
```

#### Error Handling Example
```php
/**
 * @example Error handling and retry logic
 * ```php
 * try {
 *     $customer = $customerResource->create([
 *         'name' => 'Jane Doe',
 *         'email' => 'jane@example.com'
 *     ]);
 *     echo "Customer created: " . $customer->getId();
 * } catch (ValidationException $e) {
 *     echo "Validation error: " . $e->getMessage();
 *     foreach ($e->getErrors() as $field => $errors) {
 *         echo "Field '$field': " . implode(', ', $errors) . "\n";
 *     }
 * } catch (RateLimitException $e) {
 *     echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
 *     sleep($e->getRetryAfter());
 *     // Retry the operation
 * } catch (NetworkException $e) {
 *     echo "Network error: " . $e->getMessage();
 *     // Implement retry logic or fallback
 * } catch (ApiException $e) {
 *     echo "API error: " . $e->getMessage();
 *     echo "Status code: " . $e->getStatusCode();
 *     echo "Response: " . $e->getResponseBody();
 * }
 * ```
 */
```

## 🎨 Example Guidelines

### Example Structure
1. **Context comment**: Brief explanation of what the example shows
2. **Setup code**: Initialize required objects and dependencies
3. **Main operation**: The primary functionality being demonstrated
4. **Output/Result**: Show expected results or output
5. **Error handling**: Include relevant error handling when appropriate

### Best Practices
- Use realistic data and scenarios
- Show both success and error cases
- Include necessary imports and setup
- Use meaningful variable names
- Add comments explaining non-obvious steps
- Show expected output when helpful
- Demonstrate error handling patterns
- Include cleanup code when needed
