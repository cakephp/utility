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
 * Filters files using multiple regular expressions (OR logic).
 *
 * A file is accepted if it matches ANY of the patterns.
 */
final class MultiplePcreFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator<mixed, \SplFileInfo> $iterator The iterator to filter
     * @param array<string> $patterns Regular expressions to match against
     */
    public function __construct(
        Iterator $iterator,
        protected readonly array $patterns,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $path = Path::normalize($this->current()->getPathname());

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
