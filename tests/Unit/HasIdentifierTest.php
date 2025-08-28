<?php

namespace Small\SwooleResourceServer\Test;

use Domain\Application\Entity\Trait\HasIdentifier;

class DummyWithId {
    use HasIdentifier;
}

test('HasIdentifier generates 26-char identifier', function (): void {
    $obj = new DummyWithId();
    $id = $obj->generateId()->getId();
    expect($id)->toBeString()->and(strlen($id))->toBe(26);
});

test('HasIdentifier base32 encoding is deterministic for same input', function (): void {
    $obj = new DummyWithId()->generateId();
    // Use reflection to access encodeBase32Crockford for a known input
    $ref = new \ReflectionClass($obj);
    $method = $ref->getMethod('encodeBase32Crockford');
    $method->setAccessible(true);

    $raw = random_bytes(16);
    $a = $method->invoke($obj, $raw);
    $b = $method->invoke($obj, $raw);
    expect($a)->toBe($b)->and(strlen($a))->toBe(26);
});
