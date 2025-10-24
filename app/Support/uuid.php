<?php
declare(strict_types=1);

function uuid(): string {
    return bin2hex(random_bytes(16));
}
