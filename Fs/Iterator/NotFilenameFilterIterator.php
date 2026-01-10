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
 * Filters out files that match any of the given filename patterns.
 *
 * This is the negative counterpart to FilenameFilterIterator.
 */
final class NotFilenameFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator $iterator The iterator to filter
     * @param array<string> $patterns Glob patterns to exclude
     */
    public function __construct(
        Iterator $iterator,
        protected array $patterns,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $filename = $this->current()->getFilename();

        foreach ($this->patterns as $pattern) {
            if (Path::matches($pattern, $filename)) {
                return false;
            }
        }

        return true;
    }
}
