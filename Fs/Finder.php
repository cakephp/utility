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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Fs;

use Cake\Utility\Filesystem as FilesystemUtil;
use Cake\Utility\Fs\Enum\DepthOperator;
use Cake\Utility\Fs\Enum\FinderMode;
use Closure;
use Generator;
use Iterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Finder provides a fluent interface for finding files and directories.
 *
 * Example usage:
 * ```php
 * // Find files
 * $finder = new Finder();
 * $files = $finder
 *     ->in('src')
 *     ->name('*.php')
 *     ->exclude('vendor')
 *     ->files();
 *
 * foreach ($files as $file) {
 *     echo $file->getPathname();
 * }
 *
 * // Find directories
 * $directories = (new Finder())
 *     ->in('src')
 *     ->exclude('vendor')
 *     ->directories();
 *
 * // Find both files and directories
 * $all = (new Finder())
 *     ->in('src')
 *     ->all();
 * ```
 */
class Finder
{
    /**
     * Base paths to search in
     *
     * @var array<string>
     */
    protected array $paths = [];

    /**
     * Name patterns to match
     *
     * @var array<string>
     */
    protected array $names = [];

    /**
     * Name patterns to exclude
     *
     * @var array<string>
     */
    protected array $notNames = [];

    /**
     * Directories to exclude
     *
     * @var array<string>
     */
    protected array $exclude = [];

    /**
     * Path patterns to include
     *
     * @var array<string>
     */
    protected array $pathPatterns = [];

    /**
     * Path patterns to exclude
     *
     * @var array<string>
     */
    protected array $notPathPatterns = [];

    /**
     * Glob patterns for full path matching
     *
     * @var array<string>
     */
    protected array $globPatterns = [];

    /**
     * Depth conditions
     *
     * @var array<array{0: \Cake\Filesystem\Enum\DepthOperator, 1: int}>
     */
    protected array $depths = [];

    /**
     * Whether to ignore hidden files
     *
     * @var bool
     */
    protected bool $ignoreHiddenFiles = true;

    /**
     * Whether to search recursively
     *
     * @var bool
     */
    protected bool $recursive = true;

    /**
     * The iteration mode (files, directories, or all)
     *
     * @var \Cake\Filesystem\Enum\FinderMode|null
     */
    protected ?FinderMode $mode = null;

    /**
     * The internal filesystem utility
     *
     * @var \Cake\Utility\Filesystem
     */
    protected FilesystemUtil $filesystem;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->filesystem = new FilesystemUtil();
    }

    /**
     * Add a path to search in.
     *
     * @param string $path The directory path
     * @return $this
     */
    public function in(string $path)
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Add a name pattern to match.
     *
     * @param string $pattern Glob pattern (e.g., '*.php')
     * @return $this
     */
    public function name(string $pattern)
    {
        $this->names[] = $pattern;

        return $this;
    }

    /**
     * Add a name pattern that must not be matched.
     *
     * @param string $pattern Glob pattern to exclude (e.g., '*.rb', '*Test.php')
     * @return $this
     */
    public function notName(string $pattern)
    {
        $this->notNames[] = $pattern;

        return $this;
    }

    /**
     * Exclude a directory from the search.
     *
     * @param string $directory Directory name to exclude
     * @return $this
     */
    public function exclude(string $directory)
    {
        $this->exclude[] = $directory;

        return $this;
    }

    /**
     * Add a path pattern that must be matched.
     *
     * @param string $pattern Path pattern (e.g., 'Controller')
     * @return $this
     */
    public function path(string $pattern)
    {
        $this->pathPatterns[] = $pattern;

        return $this;
    }

    /**
     * Add a path pattern that must not be matched.
     *
     * @param string $pattern Path pattern to exclude
     * @return $this
     */
    public function notPath(string $pattern)
    {
        $this->notPathPatterns[] = $pattern;

        return $this;
    }

    /**
     * Add a glob pattern for full path matching.
     *
     * Supports wildcards like `src/**\/*.php` for recursive matching.
     *
     * @param string $pattern Glob pattern (e.g., 'src/**\/*.php', 'tests/**\/*Test.php')
     * @return $this
     */
    public function pattern(string $pattern)
    {
        $this->globPatterns[] = $pattern;

        return $this;
    }

    /**
     * Add a depth condition.
     *
     * @param int $level The depth level (0 = top-level directory)
     * @param \Cake\Filesystem\Enum\DepthOperator $operator The comparison operator (default: EQUAL)
     * @return $this
     */
    public function depth(int $level, DepthOperator $operator = DepthOperator::EQUAL)
    {
        $this->depths[] = [$operator, $level];

        return $this;
    }

    /**
     * Set whether to ignore hidden files and directories.
     *
     * @param bool $ignore Whether to ignore hidden files
     * @return $this
     */
    public function ignoreHiddenFiles(bool $ignore = true)
    {
        $this->ignoreHiddenFiles = $ignore;

        return $this;
    }

    /** Set whether to search recursively into subdirectories.
     *
     * @param bool $recursive Whether to search recursively (default: true)
     * @return $this
     */
    public function recursive(bool $recursive = true)
    {
        $this->recursive = $recursive;

        return $this;
    }

    /**
     * Get files matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    public function files(): Iterator
    {
        $this->mode = FinderMode::FILES;

        return $this->iterate();
    }

    /**
     * Get directories matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    public function directories(): Iterator
    {
        $this->mode = FinderMode::DIRECTORIES;

        return $this->iterate();
    }

    /**
     * Get both files and directories matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    public function all(): Iterator
    {
        $this->mode = FinderMode::ALL;

        return $this->iterate();
    }

    /**
     * Iterate over items matching the criteria.
     *
     * @return \Generator<\SplFileInfo>
     */
    protected function iterate(): Generator
    {
        foreach ($this->paths as $path) {
            if (!$this->recursive || $this->isDepthZero()) {
                yield from $this->iterateNonRecursive($path);
            } else {
                yield from $this->iterateRecursive($path);
            }
        }
    }

    /**
     * Check if file/directory matches the mode filter.
     *
     * @param \SplFileInfo $file The file or directory to check
     * @return bool
     */
    protected function matchesMode(SplFileInfo $file): bool
    {
        return match ($this->mode) {
            FinderMode::FILES => $file->isFile(),
            FinderMode::DIRECTORIES => $file->isDir(),
            FinderMode::ALL => true,
            null => true,
        };
    }

    /**
     * Check if depth is limited to zero (top-level only).
     *
     * @return bool
     */
    protected function isDepthZero(): bool
    {
        foreach ($this->depths as $condition) {
            if ($condition === [DepthOperator::EQUAL, 0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Iterate recursively through a directory.
     *
     * @param string $path The directory path
     * @return \Generator<\SplFileInfo>
     */
    protected function iterateRecursive(string $path): Generator
    {
        $normalizedBasePath = Path::normalize($path);

        // Use SELF_FIRST when looking for directories to include them in iteration
        // Use LEAVES_ONLY when looking for files only for optimization
        $iteratorMode = $this->mode === FinderMode::FILES
            ? RecursiveIteratorIterator::LEAVES_ONLY
            : RecursiveIteratorIterator::SELF_FIRST;

        $iterator = $this->filesystem->createRecursiveIterator(
            $path,
            mode: $iteratorMode,
            includeHiddenDirs: !$this->ignoreHiddenFiles,
            customFilter: $this->buildFilter(),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$this->matchesMode($file)) {
                continue;
            }

            if ($this->ignoreHiddenFiles && str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            if (!$this->matchesNamePatterns($file)) {
                continue;
            }

            if (!$this->matchesPathPatterns($file)) {
                continue;
            }

            if (!$this->matchesDepth($file, $normalizedBasePath)) {
                continue;
            }

            if (!$this->matchesGlobPatterns($file, $normalizedBasePath)) {
                continue;
            }

            yield $file;
        }
    }

    /**
     * Iterate non-recursively through a directory.
     *
     * @param string $path The directory path
     * @return \Generator<\SplFileInfo>
     */
    protected function iterateNonRecursive(string $path): Generator
    {
        $iterator = $this->filesystem->createIterator(
            $path,
            customFilter: $this->buildNonRecursiveFilter(),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$this->matchesMode($file)) {
                continue;
            }

            if ($this->ignoreHiddenFiles && str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            if (!$this->matchesNamePatterns($file)) {
                continue;
            }

            yield $file;
        }
    }

    /**
     * Build a filter callback for the recursive iterator.
     *
     * @return \Closure|null
     */
    protected function buildFilter(): ?Closure
    {
        if ($this->exclude === [] && $this->notPathPatterns === []) {
            return null;
        }

        return function (SplFileInfo $file): bool {
            // Check excluded directories
            foreach ($this->exclude as $excluded) {
                if ($file->isDir() && str_contains($file->getPathname(), DIRECTORY_SEPARATOR . $excluded)) {
                    return false;
                }
            }

            // Check excluded path patterns
            foreach ($this->notPathPatterns as $pattern) {
                if (str_contains($file->getPathname(), $pattern)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * Build a filter callback for the non-recursive iterator.
     *
     * @return \Closure|null
     */
    protected function buildNonRecursiveFilter(): ?Closure
    {
        if ($this->exclude === []) {
            return null;
        }

        return function (SplFileInfo $file): bool {
            if (!$file->isDir()) {
                return true;
            }

            $filename = $file->getFilename();
            foreach ($this->exclude as $excluded) {
                if ($filename === $excluded) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * Check if file matches name patterns.
     *
     * @param \SplFileInfo $file The file to check
     * @return bool
     */
    protected function matchesNamePatterns(SplFileInfo $file): bool
    {
        $filename = $file->getFilename();

        // Check negative patterns first
        foreach ($this->notNames as $pattern) {
            if (Path::matches($pattern, $filename)) {
                return false;
            }
        }

        // If no positive patterns, accept all
        if ($this->names === []) {
            return true;
        }

        // Must match at least one positive pattern
        foreach ($this->names as $pattern) {
            if (Path::matches($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file matches path patterns.
     *
     * @param \SplFileInfo $file The file to check
     * @return bool
     */
    protected function matchesPathPatterns(SplFileInfo $file): bool
    {
        // Must match all include patterns
        if ($this->pathPatterns !== []) {
            $matched = false;
            foreach ($this->pathPatterns as $pattern) {
                if (str_contains($file->getPathname(), $pattern)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if file matches glob patterns.
     *
     * @param \SplFileInfo $file The file to check
     * @param string $normalizedBasePath The normalized base path to calculate relative path from
     * @return bool
     */
    protected function matchesGlobPatterns(SplFileInfo $file, string $normalizedBasePath): bool
    {
        if ($this->globPatterns === []) {
            return true;
        }

        $relativePath = Path::makeRelative($file->getPathname(), $normalizedBasePath);

        foreach ($this->globPatterns as $pattern) {
            if (Path::matches($pattern, $relativePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file matches depth conditions.
     *
     * @param \SplFileInfo $file The file to check
     * @param string $normalizedBasePath The normalized base path to calculate depth from
     * @return bool
     */
    protected function matchesDepth(SplFileInfo $file, string $normalizedBasePath): bool
    {
        if ($this->depths === []) {
            return true;
        }

        $filePath = Path::normalize($file->getPath());
        $relativePath = Path::makeRelative($filePath, $normalizedBasePath);

        $depth = $relativePath === '' ? 0 : count(explode('/', $relativePath));

        foreach ($this->depths as [$operator, $level]) {
            if (!$this->evaluateDepthCondition($depth, $operator, $level)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a depth condition.
     *
     * @param int $depth The actual depth
     * @param \Cake\Filesystem\Enum\DepthOperator $operator The comparison operator
     * @param int $level The target depth level
     * @return bool
     */
    protected function evaluateDepthCondition(int $depth, DepthOperator $operator, int $level): bool
    {
        return match ($operator) {
            DepthOperator::EQUAL => $depth === $level,
            DepthOperator::NOT_EQUAL => $depth !== $level,
            DepthOperator::LESS_THAN => $depth < $level,
            DepthOperator::GREATER_THAN => $depth > $level,
            DepthOperator::LESS_THAN_OR_EQUAL => $depth <= $level,
            DepthOperator::GREATER_THAN_OR_EQUAL => $depth >= $level,
        };
    }
}
