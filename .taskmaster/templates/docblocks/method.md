# 🔧 Method Documentation Templates

This file contains standardized PHPDoc templates for method-level documentation in the XGATE PHP SDK.

## 📋 Basic Method Template

```php
/**
 * [Brief one-line description of what the method does]
 *
 * [Detailed description explaining:
 * - What the method accomplishes
 * - How it works (algorithm, process)
 * - Important side effects or state changes
 * - When to use this method]
 *
 * @param type $paramName [Description of parameter and its purpose]
 * @param type $optionalParam [Description] (optional, default: value)
 *
 * @return type [Description of return value and its meaning]
 *
 * @throws ExceptionType [When and why this exception is thrown]
 *
 * @example Basic usage
 * ```php
 * $result = $object->methodName($param1, $param2);
 * echo "Result: " . $result;
 * ```
 */
public function methodName($paramName, $optionalParam = 'default'): ReturnType
{
    // Implementation
}
```

## 🎯 Specialized Method Templates

### API Resource Methods

#### Create Method
```php
/**
 * Create a new [resource] via the XGATE API
 *
 * Sends a POST request to create a new [resource] with the provided data.
 * The method validates input parameters, handles API communication, and
 * returns a properly formatted DTO object.
 *
 * @param string $param1 [Primary identifier or data field]
 * @param array<string, mixed> $additionalData Additional [resource] data (optional)
 * @param array<string, string> $headers Custom HTTP headers (optional)
 *
 * @return ResourceDto The created [resource] object with assigned ID
 *
 * @throws ValidationException When required parameters are missing or invalid
 * @throws ApiException When the API returns an error response
 * @throws NetworkException When network communication fails
 *
 * @example Creating a new [resource]
 * ```php
 * $resource = $resourceApi->create(
 *     'required-value',
 *     ['field1' => 'value1', 'field2' => 'value2']
 * );
 * echo "Created [resource] with ID: " . $resource->getId();
 * ```
 */
public function create(string $param1, array $additionalData = [], array $headers = []): ResourceDto
{
    // Implementation
}
```

#### Get Method
```php
/**
 * Retrieve a [resource] by its unique identifier
 *
 * Fetches a single [resource] from the XGATE API using its ID.
 * Returns null if the [resource] doesn't exist or the user doesn't
 * have permission to access it.
 *
 * @param string $id The unique identifier of the [resource]
 * @param array<string, string> $headers Custom HTTP headers (optional)
 *
 * @return ResourceDto|null The [resource] object or null if not found
 *
 * @throws ValidationException When the ID format is invalid
 * @throws ApiException When the API returns an error response
 * @throws NetworkException When network communication fails
 *
 * @example Retrieving a [resource]
 * ```php
 * $resource = $resourceApi->get('[resource]-123');
 * if ($resource) {
 *     echo "Found [resource]: " . $resource->getName();
 * } else {
 *     echo "[Resource] not found or access denied";
 * }
 * ```
 */
public function get(string $id, array $headers = []): ?ResourceDto
{
    // Implementation
}
```

#### List Method
```php
/**
 * List [resources] with optional filtering and pagination
 *
 * Retrieves a paginated list of [resources] from the XGATE API.
 * Supports filtering by [relevant criteria] and standard pagination
 * parameters. Results are returned in descending order by creation date.
 *
 * @param array<string, mixed> $filters Filtering criteria (optional)
 * @param int $page Page number for pagination (default: 1)
 * @param int $limit Number of results per page (default: 20, max: 100)
 * @param array<string, string> $headers Custom HTTP headers (optional)
 *
 * @return array{data: ResourceDto[], pagination: array{current_page: int, total_pages: int, total_items: int}}
 *
 * @throws ValidationException When pagination parameters are invalid
 * @throws ApiException When the API returns an error response
 * @throws NetworkException When network communication fails
 *
 * @example Listing [resources] with pagination
 * ```php
 * // Get first page with default settings
 * $result = $resourceApi->list();
 * 
 * // Get filtered results
 * $result = $resourceApi->list([
 *     'status' => 'active',
 *     'created_after' => '2024-01-01'
 * ], 2, 50);
 * 
 * foreach ($result['data'] as $resource) {
 *     echo $resource->getName() . "\n";
 * }
 * echo "Page {$result['pagination']['current_page']} of {$result['pagination']['total_pages']}";
 * ```
 */
public function list(array $filters = [], int $page = 1, int $limit = 20, array $headers = []): array
{
    // Implementation
}
```

#### Update Method
```php
/**
 * Update an existing [resource] with new data
 *
 * Sends a PUT/PATCH request to update the specified [resource].
 * Only provided fields will be updated; omitted fields remain unchanged.
 * Returns the updated [resource] object with current data.
 *
 * @param string $id The unique identifier of the [resource] to update
 * @param array<string, mixed> $updateData Fields to update with their new values
 * @param array<string, string> $headers Custom HTTP headers (optional)
 *
 * @return ResourceDto The updated [resource] object
 *
 * @throws ValidationException When the ID or update data is invalid
 * @throws ApiException When the API returns an error response (including 404)
 * @throws NetworkException When network communication fails
 *
 * @example Updating a [resource]
 * ```php
 * $updated = $resourceApi->update('[resource]-123', [
 *     'name' => 'New Name',
 *     'status' => 'active'
 * ]);
 * echo "Updated [resource]: " . $updated->getName();
 * ```
 */
public function update(string $id, array $updateData, array $headers = []): ResourceDto
{
    // Implementation
}
```

#### Delete Method
```php
/**
 * Delete a [resource] by its unique identifier
 *
 * Permanently removes the specified [resource] from the system.
 * This action cannot be undone. Returns true if the deletion was
 * successful or if the [resource] didn't exist.
 *
 * @param string $id The unique identifier of the [resource] to delete
 * @param array<string, string> $headers Custom HTTP headers (optional)
 *
 * @return bool True if deletion was successful, false otherwise
 *
 * @throws ValidationException When the ID format is invalid
 * @throws ApiException When the API returns an error response
 * @throws NetworkException When network communication fails
 *
 * @example Deleting a [resource]
 * ```php
 * $success = $resourceApi->delete('[resource]-123');
 * if ($success) {
 *     echo "[Resource] deleted successfully";
 * } else {
 *     echo "Failed to delete [resource]";
 * }
 * ```
 */
public function delete(string $id, array $headers = []): bool
{
    // Implementation
}
```

### DTO Methods

#### Constructor
```php
/**
 * Create a new [Entity] DTO instance
 *
 * Initializes a new [Entity] object with the provided data.
 * Performs basic type validation but does not enforce business rules.
 * Use static factory methods for more complex creation scenarios.
 *
 * @param string $id Unique identifier for the [entity]
 * @param string $name Display name of the [entity]
 * @param array<string, mixed> $additionalData Additional [entity] properties (optional)
 *
 * @example Creating a new [entity] DTO
 * ```php
 * $entity = new Entity('entity-123', 'Entity Name', [
 *     'description' => 'Entity description',
 *     'metadata' => ['key' => 'value']
 * ]);
 * ```
 */
public function __construct(
    private string $id,
    private string $name,
    private array $additionalData = []
) {
    // Implementation
}
```

#### Factory Method (fromArray)
```php
/**
 * Create [Entity] instance from array data
 *
 * Factory method that creates a new [Entity] DTO from an associative array,
 * typically received from API responses. Handles data transformation and
 * provides default values for missing fields.
 *
 * @param array<string, mixed> $data Raw data array from API response
 *
 * @return static New [Entity] instance populated with the provided data
 *
 * @throws ValidationException When required fields are missing or invalid
 *
 * @example Creating from API response
 * ```php
 * $apiData = json_decode($response, true);
 * $entity = Entity::fromArray($apiData);
 * echo "Loaded entity: " . $entity->getName();
 * ```
 */
public static function fromArray(array $data): static
{
    // Implementation
}
```

#### Serialization Method (toArray)
```php
/**
 * Convert [Entity] to array representation
 *
 * Serializes the [Entity] object to an associative array suitable for
 * JSON encoding or API requests. Includes all public properties and
 * handles nested objects appropriately.
 *
 * @return array<string, mixed> Array representation of the [entity]
 *
 * @example Converting to array for API request
 * ```php
 * $entity = new Entity('id', 'name');
 * $requestData = $entity->toArray();
 * $json = json_encode($requestData);
 * ```
 */
public function toArray(): array
{
    // Implementation
}
```

### Utility Methods

#### Validation Method
```php
/**
 * Validate [specific data or state] according to business rules
 *
 * Performs comprehensive validation of [data/state] against defined
 * business rules and constraints. Returns detailed validation results
 * that can be used for error reporting or conditional logic.
 *
 * @param mixed $data The data to validate
 * @param array<string, mixed> $context Additional validation context (optional)
 *
 * @return array{valid: bool, errors: string[], warnings: string[]} Validation results
 *
 * @example Validating user input
 * ```php
 * $result = $validator->validate($userData, ['strict' => true]);
 * if (!$result['valid']) {
 *     foreach ($result['errors'] as $error) {
 *         echo "Error: " . $error . "\n";
 *     }
 * }
 * ```
 */
public function validate($data, array $context = []): array
{
    // Implementation
}
```

#### Configuration Method
```php
/**
 * Configure [component/service] with provided settings
 *
 * Applies configuration settings to the [component], validating each
 * setting and providing sensible defaults for missing values. Configuration
 * is applied immediately and affects subsequent operations.
 *
 * @param array<string, mixed> $config Configuration settings to apply
 * @param bool $merge Whether to merge with existing config (default: true)
 *
 * @return self Returns self for method chaining
 *
 * @throws ConfigurationException When configuration values are invalid
 *
 * @example Configuring the service
 * ```php
 * $service->configure([
 *     'timeout' => 30,
 *     'retries' => 3,
 *     'debug' => true
 * ])->performOperation();
 * ```
 */
public function configure(array $config, bool $merge = true): self
{
    // Implementation
}
```

### Private/Protected Methods

```php
/**
 * [Brief description of internal method purpose]
 *
 * [Detailed explanation of:
 * - What this internal method does
 * - How it fits into the larger process
 * - Important implementation details
 * - Assumptions or preconditions]
 *
 * @param type $param Parameter description
 *
 * @return type Return value description
 *
 * @throws ExceptionType When specific internal error occurs
 */
private function internalMethod($param): ReturnType
{
    // Implementation
}
```

## 🏷️ Method Documentation Tags

### Required Tags
- `@param` - For each parameter, including type and description
- `@return` - For non-void methods, including type and description

### Exception Tags
- `@throws` - For each exception type that can be thrown
- Include both checked and runtime exceptions
- Explain when and why each exception occurs

### Optional Tags
- `@since` - Version when method was introduced
- `@deprecated` - Mark as deprecated with alternative
- `@see` - Reference related methods or documentation
- `@internal` - Mark as internal implementation detail
- `@api` - Mark as stable public API

### Documentation Tags
- `@example` - Always include for public methods
- `@link` - External documentation or RFC links

## 🎨 Method Documentation Guidelines

### Parameter Documentation
```php
@param string $id The unique identifier (required, format: prefix-uuid)
@param array<string, mixed> $data Associative array of field values (optional)
@param bool $strict Enable strict validation mode (default: false)
```

### Return Documentation
```php
@return ResourceDto The created resource with generated ID and timestamps
@return array{success: bool, data: mixed, errors: string[]} Operation result with status
@return Generator<int, ResourceDto> Lazy-loaded resource iterator
```

### Exception Documentation
```php
@throws ValidationException When required parameters are missing or malformed
@throws ApiException When the API returns 4xx or 5xx response codes
@throws NetworkException When HTTP request fails due to connectivity issues
```

### Example Quality Standards
- Show realistic, working code
- Include error handling when relevant
- Demonstrate both simple and complex usage
- Use meaningful variable names and values
- Show expected output when helpful

### Description Guidelines
- Start with action verb (e.g., "Creates", "Retrieves", "Validates")
- Explain the "what" and "why", not just the "how"
- Include important side effects or state changes
- Mention performance implications for expensive operations
- Reference related methods or concepts when helpful 