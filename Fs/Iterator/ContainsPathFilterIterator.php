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
use FilterIterator;
use Iterator;

/**
 * Filters files to only include those whose path contains at least one
 * of the given patterns.
 *
 * Uses simple string matching (str_contains) with OR logic.
 */
final class ContainsPathFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator $iterator The iterator to filter
     * @param array<string> $patterns Path patterns to include
     */
    public function __construct(
        Iterator $iterator,
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
        $path = Path::normalize($this->current()->getPathname());

        foreach ($this->patterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
