<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Allow to build rules to find files and directories.
 *
 * All rules may be invoked several times, except for ->in() method.
 * Some rules are cumulative (->name() for example) whereas others are destructive
 * (most recent value is used, ->maxdepth() method for example).
 *
 * All methods return the current sfFinder object to allow easy chaining:
 *
 * $files = sfFinder::type('file')->name('*.php')->in(.);
 *
 * Interface loosely based on perl File::Find::Rule module.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfFinder
{
    protected $type = 'file';
    protected $names = array();
    protected $prunes = array();
    protected $discards = array();
    protected $execs = array();
    protected $mindepth = 0;
    protected $sizes = array();
    protected $maxdepth = 1000000;
    protected $relative = false;
    protected $follow_link = false;
    protected $sort = false;
    protected $ignore_version_control = true;

    /**
     * Sets maximum directory depth.
     *
     * Finder will descend at most $level levels of directories below the starting point.
     *
     * @param int $level
     *
     * @return sfFinder current sfFinder object
     */
    public function maxdepth($level)
    {
        $this->maxdepth = $level;

        return $this;
    }

    /**
     * Sets minimum directory depth.
     *
     * Finder will start applying tests at level $level.
     *
     * @param int $level
     *
     * @return sfFinder current sfFinder object
     */
    public function mindepth($level)
    {
        $this->mindepth = $level;

        return $this;
    }

    public function get_type()
    {
        return $this->type;
    }

    /**
     * Sets the type of elements to returns.
     *
     * @param string $name directory or file or any (for both file and directory)
     *
     * @return sfFinder new sfFinder object
     */
    public static function type($name)
    {
        $finder = new self();

        return $finder->setType($name);
    }

    /**
     * Sets the type of elements to returns.
     *
     * @param string $name directory or file or any (for both file and directory)
     *
     * @return sfFinder Current object
     */
    public function setType($name)
    {
        $name = strtolower($name);

        if ('dir' === substr($name, 0, 3)) {
            $this->type = 'directory';

            return $this;
        }
        if ('any' === $name) {
            $this->type = 'any';

            return $this;
        }

        $this->type = 'file';

        return $this;
    }

    /**
     * Adds rules that files must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->name('*.php')
     * $finder->name('/\.php$/') // same as above
     * $finder->name('test.php')
     *
     * @param  list   a list of patterns, globs or strings
     *
     * @return sfFinder Current object
     */
    public function name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args));

        return $this;
    }

    /**
     * Adds rules that files must not match.
     *
     * @see    ->name()
     *
     * @param  list   a list of patterns, globs or strings
     *
     * @return sfFinder Current object
     */
    public function not_name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args, true));

        return $this;
    }

    /**
     * Adds tests for file sizes.
     *
     * $finder->size('> 10K');
     * $finder->size('<= 1Ki');
     * $finder->size(4);
     *
     * @param  list   a list of comparison strings
     *
     * @return sfFinder Current object
     */
    public function size()
    {
        $args = func_get_args();
        $numargs = count($args);
        for ($i = 0; $i < $numargs; ++$i) {
            $this->sizes[] = new sfNumberCompare($args[$i]);
        }

        return $this;
    }

    /**
     * Traverses no further.
     *
     * @param  list   a list of patterns, globs to match
     *
     * @return sfFinder Current object
     */
    public function prune()
    {
        $args = func_get_args();
        $this->prunes = array_merge($this->prunes, $this->args_to_array($args));

        return $this;
    }

    /**
     * Discards elements that matches.
     *
     * @param  list   a list of patterns, globs to match
     *
     * @return sfFinder Current object
     */
    public function discard()
    {
        $args = func_get_args();
        $this->discards = array_merge($this->discards, $this->args_to_array($args));

        return $this;
    }

    /**
     * Ignores version control directories.
     *
     * Currently supports Subversion, CVS, DARCS, Gnu Arch, Monotone, Bazaar-NG, GIT, Mercurial
     *
     * @param bool $ignore falase when version control directories shall be included (default is true)
     *
     * @return sfFinder Current object
     */
    public function ignore_version_control($ignore = true)
    {
        $this->ignore_version_control = $ignore;

        return $this;
    }

    /**
     * Returns files and directories ordered by name.
     *
     * @return sfFinder Current object
     */
    public function sort_by_name()
    {
        $this->sort = 'name';

        return $this;
    }

    /**
     * Returns files and directories ordered by type (directories before files), then by name.
     *
     * @return sfFinder Current object
     */
    public function sort_by_type()
    {
        $this->sort = 'type';

        return $this;
    }

    /**
     * Executes function or method for each element.
     *
     * Element match if functino or method returns true.
     *
     * $finder->exec('myfunction');
     * $finder->exec(array($object, 'mymethod'));
     *
     * @param  mixed  function or method to call
     *
     * @return sfFinder Current object
     */
    public function exec()
    {
        $args = func_get_args();
        $numargs = count($args);
        for ($i = 0; $i < $numargs; ++$i) {
            if (is_array($args[$i]) && !method_exists($args[$i][0], $args[$i][1])) {
                throw new sfException(sprintf('method "%s" does not exist for object "%s".', $args[$i][1], $args[$i][0]));
            }
            if (!is_array($args[$i]) && !function_exists($args[$i])) {
                throw new sfException(sprintf('function "%s" does not exist.', $args[$i]));
            }

            $this->execs[] = $args[$i];
        }

        return $this;
    }

    /**
     * Returns relative paths for all files and directories.
     *
     * @return sfFinder Current object
     */
    public function relative()
    {
        $this->relative = true;

        return $this;
    }

    /**
     * Symlink following.
     *
     * @return sfFinder Current object
     */
    public function follow_link()
    {
        $this->follow_link = true;

        return $this;
    }

    /**
     * Searches files and directories which match defined rules.
     *
     * @return array list of files and directories
     */
    public function in()
    {
        $files = array();
        $here_dir = getcwd();

        $finder = clone $this;

        if ($this->ignore_version_control) {
            $ignores = array('.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg');

            $finder->discard($ignores)->prune($ignores);
        }

        // first argument is an array?
        $numargs = func_num_args();
        $arg_list = func_get_args();
        if (1 === $numargs && is_array($arg_list[0])) {
            $arg_list = $arg_list[0];
            $numargs = count($arg_list);
        }

        for ($i = 0; $i < $numargs; ++$i) {
            $dir = realpath($arg_list[$i]);

            if (!is_dir($dir)) {
                continue;
            }

            $dir = str_replace('\\', '/', $dir);

            // absolute path?
            if (!self::isPathAbsolute($dir)) {
                $dir = $here_dir.'/'.$dir;
            }

            $new_files = str_replace('\\', '/', $finder->search_in($dir));

            if ($this->relative) {
                $new_files = preg_replace('#^'.preg_quote(rtrim($dir, '/'), '#').'/#', '', $new_files);
            }

            $files = array_merge($files, $new_files);
        }

        if ('name' === $this->sort) {
            sort($files);
        }

        return array_unique($files);
    }

    public static function isPathAbsolute($path)
    {
        if ('/' === $path[0] || '\\' === $path[0]
            || (
                strlen($path) > 3 && ctype_alpha($path[0])
             && ':' === $path[1]
             && ('\\' === $path[2] || '/' === $path[2])
            )
        ) {
            return true;
        }

        return false;
    }

    // glob, patterns (must be //) or strings
    protected function to_regex($str)
    {
        if (preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $str)) {
            return $str;
        }

        return sfGlobToRegex::glob_to_regex($str);
    }

    protected function args_to_array($arg_list, $not = false)
    {
        $list = array();
        $nbArgList = count($arg_list);
        for ($i = 0; $i < $nbArgList; ++$i) {
            if (is_array($arg_list[$i])) {
                foreach ($arg_list[$i] as $arg) {
                    $list[] = array($not, $this->to_regex($arg));
                }
            } else {
                $list[] = array($not, $this->to_regex($arg_list[$i]));
            }
        }

        return $list;
    }

    protected function search_in($dir, $depth = 0)
    {
        if ($depth > $this->maxdepth) {
            return array();
        }

        $dir = realpath($dir);

        if ((!$this->follow_link) && is_link($dir)) {
            return array();
        }

        $files = array();
        $temp_files = array();
        $temp_folders = array();
        if (is_dir($dir) && is_readable($dir)) {
            $current_dir = opendir($dir);
            while (false !== $entryname = readdir($current_dir)) {
                if ('.' == $entryname || '..' == $entryname) {
                    continue;
                }

                $current_entry = $dir.DIRECTORY_SEPARATOR.$entryname;
                if ((!$this->follow_link) && is_link($current_entry)) {
                    continue;
                }

                if (is_dir($current_entry)) {
                    if ('type' === $this->sort) {
                        $temp_folders[$entryname] = $current_entry;
                    } else {
                        if (('directory' === $this->type || 'any' === $this->type) && ($depth >= $this->mindepth) && !$this->is_discarded($dir, $entryname) && $this->match_names($dir, $entryname) && $this->exec_ok($dir, $entryname)) {
                            $files[] = $current_entry;
                        }

                        if (!$this->is_pruned($dir, $entryname)) {
                            $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
                        }
                    }
                } else {
                    if (('directory' !== $this->type || 'any' === $this->type) && ($depth >= $this->mindepth) && !$this->is_discarded($dir, $entryname) && $this->match_names($dir, $entryname) && $this->size_ok($dir, $entryname) && $this->exec_ok($dir, $entryname)) {
                        if ('type' === $this->sort) {
                            $temp_files[] = $current_entry;
                        } else {
                            $files[] = $current_entry;
                        }
                    }
                }
            }

            if ('type' === $this->sort) {
                ksort($temp_folders);
                foreach ($temp_folders as $entryname => $current_entry) {
                    if (('directory' === $this->type || 'any' === $this->type) && ($depth >= $this->mindepth) && !$this->is_discarded($dir, $entryname) && $this->match_names($dir, $entryname) && $this->exec_ok($dir, $entryname)) {
                        $files[] = $current_entry;
                    }

                    if (!$this->is_pruned($dir, $entryname)) {
                        $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
                    }
                }

                sort($temp_files);
                $files = array_merge($files, $temp_files);
            }

            closedir($current_dir);
        }

        return $files;
    }

    protected function match_names($dir, $entry)
    {
        if (!count($this->names)) {
            return true;
        }

        // Flags indicating that there was attempts to match
        // at least one "not_name" or "name" rule respectively
        // to following variables:
        $one_not_name_rule = false;
        $one_name_rule = false;

        foreach ($this->names as $args) {
            list($not, $regex) = $args;
            $not ? $one_not_name_rule = true : $one_name_rule = true;
            if (preg_match($regex, $entry)) {
                // We must match ONLY ONE "not_name" or "name" rule:
                // if "not_name" rule matched then we return "false"
                // if "name" rule matched then we return "true"
                return $not ? false : true;
            }
        }

        if ($one_not_name_rule && $one_name_rule) {
            return false;
        }
        if ($one_not_name_rule) {
            return true;
        }
        if ($one_name_rule) {
            return false;
        }

        return true;
    }

    protected function size_ok($dir, $entry)
    {
        if (0 === count($this->sizes)) {
            return true;
        }

        if (!is_file($dir.DIRECTORY_SEPARATOR.$entry)) {
            return true;
        }

        $filesize = filesize($dir.DIRECTORY_SEPARATOR.$entry);
        foreach ($this->sizes as $number_compare) {
            if (!$number_compare->test($filesize)) {
                return false;
            }
        }

        return true;
    }

    protected function is_pruned($dir, $entry)
    {
        if (0 === count($this->prunes)) {
            return false;
        }

        foreach ($this->prunes as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }

        return false;
    }

    protected function is_discarded($dir, $entry)
    {
        if (0 === count($this->discards)) {
            return false;
        }

        foreach ($this->discards as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }

        return false;
    }

    protected function exec_ok($dir, $entry)
    {
        if (0 === count($this->execs)) {
            return true;
        }

        foreach ($this->execs as $exec) {
            if (!call_user_func_array($exec, array($dir, $entry))) {
                return false;
            }
        }

        return true;
    }
}

/**
 * Match globbing patterns against text.
 *
 *   if match_glob("foo.*", "foo.bar") echo "matched\n";
 *
 * // prints foo.bar and foo.baz
 * $regex = glob_to_regex("foo.*");
 * for (array('foo.bar', 'foo.baz', 'foo', 'bar') as $t)
 * {
 *   if (/$regex/) echo "matched: $car\n";
 * }
 *
 * sfGlobToRegex implements glob(3) style matching that can be used to match
 * against text, rather than fetching names from a filesystem.
 *
 * based on perl Text::Glob module.
 *
 * @author     Fabien Potencier <fabien.potencier@gmail.com> php port
 * @author     Richard Clamp <richardc@unixbeard.net> perl version
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@gmail.com>
 * @copyright  2002 Richard Clamp <richardc@unixbeard.net>
 */
class sfGlobToRegex
{
    protected static $strict_leading_dot = true;
    protected static $strict_wildcard_slash = true;

    public static function setStrictLeadingDot($boolean)
    {
        self::$strict_leading_dot = $boolean;
    }

    public static function setStrictWildcardSlash($boolean)
    {
        self::$strict_wildcard_slash = $boolean;
    }

    /**
     * Returns a compiled regex which is the equiavlent of the globbing pattern.
     *
     * @param string $glob pattern
     *
     * @return string regex
     */
    public static function glob_to_regex($glob)
    {
        $first_byte = true;
        $escaping = false;
        $in_curlies = 0;
        $regex = '';
        $sizeGlob = strlen($glob);
        for ($i = 0; $i < $sizeGlob; ++$i) {
            $car = $glob[$i];
            if ($first_byte) {
                if (self::$strict_leading_dot && '.' !== $car) {
                    $regex .= '(?=[^\.])';
                }

                $first_byte = false;
            }

            if ('/' === $car) {
                $first_byte = true;
            }

            if ('.' === $car || '(' === $car || ')' === $car || '|' === $car || '+' === $car || '^' === $car || '$' === $car) {
                $regex .= "\\{$car}";
            } elseif ('*' === $car) {
                $regex .= ($escaping ? '\\*' : (self::$strict_wildcard_slash ? '[^/]*' : '.*'));
            } elseif ('?' === $car) {
                $regex .= ($escaping ? '\\?' : (self::$strict_wildcard_slash ? '[^/]' : '.'));
            } elseif ('{' === $car) {
                $regex .= ($escaping ? '\\{' : '(');
                if (!$escaping) {
                    ++$in_curlies;
                }
            } elseif ('}' === $car && $in_curlies) {
                $regex .= ($escaping ? '}' : ')');
                if (!$escaping) {
                    --$in_curlies;
                }
            } elseif (',' === $car && $in_curlies) {
                $regex .= ($escaping ? ',' : '|');
            } elseif ('\\' === $car) {
                if ($escaping) {
                    $regex .= '\\\\';
                    $escaping = false;
                } else {
                    $escaping = true;
                }

                continue;
            } else {
                $regex .= $car;
            }
            $escaping = false;
        }

        return '#^'.$regex.'$#';
    }
}

/**
 * Numeric comparisons.
 *
 * sfNumberCompare compiles a simple comparison to an anonymous
 * subroutine, which you can call with a value to be tested again.
 *
 * Now this would be very pointless, if sfNumberCompare didn't understand
 * magnitudes.
 *
 * The target value may use magnitudes of kilobytes (k, ki),
 * megabytes (m, mi), or gigabytes (g, gi).  Those suffixed
 * with an i use the appropriate 2**n version in accordance with the
 * IEC standard: http://physics.nist.gov/cuu/Units/binary.html
 *
 * based on perl Number::Compare module.
 *
 * @author     Fabien Potencier <fabien.potencier@gmail.com> php port
 * @author     Richard Clamp <richardc@unixbeard.net> perl version
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@gmail.com>
 * @copyright  2002 Richard Clamp <richardc@unixbeard.net>
 *
 * @see        http://physics.nist.gov/cuu/Units/binary.html
 */
class sfNumberCompare
{
    protected $test = '';

    public function __construct($test)
    {
        $this->test = $test;
    }

    public function test($number)
    {
        if (!preg_match('{^([<>]=?)?(.*?)([kmg]i?)?$}i', $this->test, $matches)) {
            throw new sfException(sprintf('don\'t understand "%s" as a test.', $this->test));
        }

        $target = array_key_exists(2, $matches) ? $matches[2] : '';
        $magnitude = array_key_exists(3, $matches) ? $matches[3] : '';
        if ('k' === strtolower($magnitude)) {
            $target *= 1000;
        }
        if ('ki' === strtolower($magnitude)) {
            $target *= 1024;
        }
        if ('m' === strtolower($magnitude)) {
            $target *= 1000000;
        }
        if ('mi' === strtolower($magnitude)) {
            $target *= 1024 * 1024;
        }
        if ('g' === strtolower($magnitude)) {
            $target *= 1000000000;
        }
        if ('gi' === strtolower($magnitude)) {
            $target *= 1024 * 1024 * 1024;
        }

        $comparison = array_key_exists(1, $matches) ? $matches[1] : '==';
        if ('==' === $comparison || '' == $comparison) {
            return $number == $target;
        }
        if ('>' === $comparison) {
            return $number > $target;
        }
        if ('>=' === $comparison) {
            return $number >= $target;
        }
        if ('<' === $comparison) {
            return $number < $target;
        }
        if ('<=' === $comparison) {
            return $number <= $target;
        }

        return false;
    }
}
