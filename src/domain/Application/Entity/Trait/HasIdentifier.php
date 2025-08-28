<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\Entity\Trait;

use Small\Forms\Form\Field\Type\StringType;
use Small\Forms\ValidationRule\ValidateNotEmpty;

trait HasIdentifier
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /** last millisecond seen (per-process, shared) */
    private static int $lastMs = 0;
    /** per-ms sequence (20 bits, shared) */
    private static int $seq = 0;

    /** Swoole mutex for coroutine-safety within the process */
    private static ?\Swoole\Lock $lock = null;

    /** 6-byte node id (binary, shared) */
    private static ?string $nodeId6 = null;

    #[StringType]
    #[ValidateNotEmpty]
    protected ?string $id = null;

    public function getId(): ?string
    {
        return $this->id; // or change signature to string and throw if null
    }

    /** Initialize lazily (once per process) */
    private static function init(): void
    {
        if (self::$nodeId6 !== null) {
            return;
        }

        // Node ID strategy:
        // 1) ENV MULID_NODE_ID as 12 hex chars (e.g., "a1b2c3d4e5f6")
        // 2) Else: derive from hostname + uname + interfaces (sha1) -> first 6 bytes
        $env = getenv('MULID_NODE_ID');
        if (is_string($env) && preg_match('/^[0-9a-fA-F]{12}$/', $env)) {
            $bin = hex2bin(strtolower($env));
            // hex2bin() returns string|false; guard it
            self::$nodeId6 = ($bin !== false) ? $bin : null;
        }

        if (self::$nodeId6 === null) {
            $src = gethostname() . '|' . php_uname();
            foreach (['/sys/class/net'] as $dir) {
                if (is_dir($dir)) {
                    foreach (scandir($dir) ?: [] as $if) {
                        $macFile = $dir . '/' . $if . '/address';
                        if (@is_file($macFile)) {
                            $mac = trim(@file_get_contents($macFile) ?: '');
                            if ($mac !== '') $src .= '|' . $mac;
                        }
                    }
                }
            }
            $hash = sha1($src, true);               // 20 bytes
            self::$nodeId6 = substr($hash, 0, 6);   // 6 bytes
        }

        // Safety: ensure length 6
        if (self::$nodeId6 === null || strlen(self::$nodeId6) !== 6) {
            // Fallback to random 6 bytes
            self::$nodeId6 = random_bytes(6);
        }

        if (class_exists(\Swoole\Lock::class) && self::$lock === null) {
            // Mutex for the short critical section
            self::$lock = new \Swoole\Lock(SWOOLE_MUTEX);
        }
    }

    /**
     * Generate a MULID-like ID (26 chars Crockford Base32)
     */
    public function generateId(): self
    {
        self::init();

        // Milliseconds since epoch; prefer hrtime to avoid float drift
        // $ms = (int) floor((hrtime(true) / 1_000_000));
        $ms = (int) floor(microtime(true) * 1000);

        $pid = getmypid() & 0xFFF; // 12 bits

        if (self::$lock) self::$lock->lock();

        // Monotonic: if time goes backward, clamp to lastMs
        if ($ms < self::$lastMs) {
            $ms = self::$lastMs;
        }

        if ($ms === self::$lastMs) {
            self::$seq = (self::$seq + 1) & 0xFFFFF; // 20 bits
            if (self::$seq === 0) {
                // Overflow: wait for next millisecond
                do {
                    // In coroutine context, yield would be nicer:
                    // if (class_exists('\\Swoole\\Coroutine')) \\Swoole\\Coroutine::yield();
                    usleep(0);
                    $ms = (int) floor(microtime(true) * 1000);
                } while ($ms <= self::$lastMs);
                self::$lastMs = $ms;
                self::$seq = 0;
            }
        } else {
            self::$lastMs = $ms;
            self::$seq = 0;
        }

        $seq = self::$seq;

        if (self::$lock) self::$lock->unlock();

        // --- Pack 128 bits ---
        $bytes = array_fill(0, 16, 0);

        // [0..5] timestamp (48 bits, big-endian)
        $ts = $ms;
        for ($i = 5; $i >= 0; $i--) {
            $bytes[$i] = $ts & 0xFF;
            $ts >>= 8;
        }

        // [6..9] = (20-bit seq << 12) | 12-bit pid (BE 32 bits)
        $mid = (($seq & 0xFFFFF) << 12) | ($pid & 0xFFF);
        $bytes[6] = ($mid >> 24) & 0xFF;
        $bytes[7] = ($mid >> 16) & 0xFF;
        $bytes[8] = ($mid >> 8)  & 0xFF;
        $bytes[9] = ($mid)       & 0xFF;

        // [10..15] node id (48 bits)
        $node = self::$nodeId6; // 6 bytes
        for ($i = 0; $i < 6; $i++) {
            $bytes[10 + $i] = ord($node[$i]);
        }

        $bin = '';
        foreach ($bytes as $b) $bin .= chr($b);

        $this->id = self::encodeBase32Crockford($bin); // 26 chars

        return $this;
    }

    /**
     * Base32 Crockford (no padding), 128 bits -> 26 chars
     */
    private static function encodeBase32Crockford(string $binary): string
    {
        $alphabet = self::ALPHABET;
        $output = '';
        $buffer = 0; // int
        $bits   = 0; // nombre de bits valides dans $buffer

        $len = strlen($binary);
        for ($i = 0; $i < $len; $i++) {
            // Pas de masque 0xFFFFFFFFFFFFFFFF ici (inutile et source du warning)
            $buffer = ($buffer << 8) | ord($binary[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $idx = ($buffer >> ($bits - 5)) & 0x1F;
                $bits -= 5;
                $output .= $alphabet[$idx];
                // On ne réduit pas explicitement $buffer ; $bits suit les bits valides
            }
        }

        if ($bits > 0) {
            $idx = ($buffer << (5 - $bits)) & 0x1F;
            $output .= $alphabet[$idx];
        }

        // 128 bits / 5 = 25.6 -> 26 symboles
        $lenOut = strlen($output);
        if ($lenOut < 26) {
            $output = str_pad($output, 26, '0', STR_PAD_LEFT);
        } elseif ($lenOut > 26) {
            $output = substr($output, 0, 26);
        }

        return $output;
    }
}
