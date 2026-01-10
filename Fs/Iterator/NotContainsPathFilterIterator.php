<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Fs\Iterator;

use Cake\Utility\Fs\Path;
use RecursiveFilterIterator;
use RecursiveIterator;

/**
 * Filters out files whose path contains any of the given patterns.
 *
 * Uses simple string matching (str_contains). Always allows directories
 * to enable recursion.
 */
final class NotContainsPathFilterIterator extends RecursiveFilterIterator
{
    /**
     * @param \RecursiveIterator $iterator The iterator to filter
     * @param array<string> $patterns Path patterns to exclude
     */
    public function __construct(
        RecursiveIterator $iterator,
        protected array $patterns,
    ) {
        parent::__construct($iterator);
        // Normalize patterns once for cross-platform compatibility
        $this->patterns = array_map(fn(string $p) => Path::normalize($p), $this->patterns);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $current = $this->current();

        // Always accept directories to allow traversal
        if ($current->isDir()) {
            return true;
        }

        // For files, check if path contains excluded patterns
        $path = Path::normalize($current->getPathname());
        foreach ($this->patterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): self
    {
        /** @var \RecursiveIterator $inner */
        $inner = $this->getInnerIterator();

        return new self($inner->getChildren(), $this->patterns);
    }
}
