<?php
/**
 * Template parser.
 * This is my version of the Template class by David Adams.
 * https://codeshack.io/lightweight-template-engine-php/
 */
class Template {

    /**
     * @var string The directory path where the compiled templates are cached.
     */
    public $cache_dir = '';

    /**
     * @var bool Enable cache
     */
    public $cache_enabled = true;

    /**
     * @var array Stores the defined blocks.
     */
    private static $blocks = [];

    /**
     * Initializes a new instance of the Template class.
     *
     * @param array $conf An optional configuration array.
     */
    public function __construct($conf = []) {
        $conf = (array) $conf;
        isset($conf['cache_dir']) &&
        $this->cache_dir = $conf['cache_dir'];
        isset($conf['cache_enabled']) &&
        $this->cache_enabled = $conf['cache_enabled'];
    }

    /**
     * Parses and outputs the template content.
     *
     * @param string $file The path to the template file.
     * @param array $data An associative array of data variables to be used in the template.
     */
    public function parse($file, $data = []) {
        $data = (array) $data;
        extract($data, EXTR_SKIP);
        ob_start();
        require $this->compile($file);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Compiles a template file and returns the compiled file path.
     *
     * @param string $file The path to the template file.
     * @return string The path to the compiled template file.
     */
    public function compile($file) {
        $cdir = self::setCacheDir();

        // Resolve cache file path
        file_exists($file) &&
        $file = realpath($file);
        $cfile = str_replace(
        '/', '_', "$file.php");
        $cfile = $cdir.$cfile;

        // Check if needs build
        $cenable = $this->cache_enabled;
        if (!file_exists($file) || !$cenable ||
        @filemtime($cfile) < @filemtime($file)) {

            // Compile templates
            $eol = PHP_EOL;
            $cls = __CLASS__;
            $code = $file;

            $code = self::compileInclude($code);
            $code = self::compileSetBlock($code);
            $code = self::compileBlock($code);
            $code = self::compilePHP($code);
            $code = self::compileEcho($code);

            // Save to cache
            file_put_contents($cfile, $code);
            chmod($cfile, 0777);
        }

        // Return compiled code
        return $cfile;
    }

    /**
     * Sets the cache directory and creates it if it doesn't exist.
     *
     * @return string The path to the cache directory.
     */
    public function setCacheDir() {
        $cdir = $this->cache_dir;
        $cdir = rtrim($cdir, "/")."/";
        !is_dir($cdir) && mkdir($cdir);
        @chmod($cdir, 0777);
        return $cdir;
    }

    /**
     * Clears the template cache by deleting all cached files.
     */
    public function clearCache() {
        $cdir = $this->setCacheDir();
        foreach (glob("$cdir*") as $f) {
            unlink($f);
        }
    }

    /**
     * Compiles "include" and "extend" statements in the template.
     *
     * @param string $file The content of the template file.
     * @return string The modified template content with compiled "include" and "extend" statements.
     */
    private static function compileInclude($file) {
        $code = file_get_contents($file);
        $regx = '/{{@ ?(include|extend) ?\'?(.*?)\'? ?}}/i';
        preg_match_all($regx, $code, $mtch, PREG_SET_ORDER);
        foreach ($mtch as $m) {
            $incl = self::compileInclude($m[2]);
            $code = str_replace($m[0], $incl, $code);
        }
        $code = preg_replace($regx, '', $code);
        return $code;
    }

    /**
     * Compiles PHP code blocks in the template.
     *
     * @param string $code The content of the template.
     * @return string The modified template content with compiled PHP code blocks.
     */
    private static function compilePHP($code) {
        $regx = '~\{{@\s*(.+?)\s*\}}~is';
        $code = preg_replace($regx, '<?php $1 ?>', $code);
        $code = preg_replace($regx, '', $code);
        return $code;
    }

    /**
     * Compiles echo statements in the template.
     * Dot notation can be used to access objects.
     *
     * @param string $code The content of the template.
     * @return string The modified template content with compiled echo statements.
     */
    private static function compileEcho($code) {
        $regx = '~\{{\s*(.+?)\s*\}}~is';
        $repl = '<?php echo \$$1 ?>';
        $code = preg_replace_callback($regx, function($m){
            $new = str_replace(".", "->", $m[1]);
            return "{{{$new}}}";
        }, $code);
        return preg_replace($regx, $repl, $code);
    }

    /**
     * Compiles "setblock" statements in the template and stores the block content.
     *
     * @param string $code The content of the template.
     * @return string The modified template content with compiled "setblock" statements.
     */
    private static function compileSetBlock($code) {
        $regx = '/{{@ ?setblock ?(.*?) ?}}(.*?){{@ ?endsetblock ?;? ?}}/is';
        preg_match_all($regx, $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            if (!array_key_exists($value[1], self::$blocks)) {
                self::$blocks[$value[1]] = '';
            }
            $blockContent = preg_replace('/{{@\s*parent\s*}}/', '{{@parent}}', $value[2]);
            if (strpos($blockContent, '{{@parent}}') === false) {
                self::$blocks[$value[1]] = $blockContent;
            } else {
                self::$blocks[$value[1]] = str_replace('{{@parent}}', self::$blocks[$value[1]], $blockContent);
            }
            $code = str_replace($value[0], '', $code);
        }
        return $code;
    }

    /**
     * Compiles "block" statements in the template and replaces them with the stored block content.
     *
     * @param string $code The content of the template.
     * @return string The modified template content with compiled "block" statements.
     */
    private static function compileBlock($code) {
        foreach (self::$blocks as $block => $value) {
            $regx = "/{{@ ?block ?$block ?}}/";
            $code = preg_replace($regx, $value, $code);
        }
        $regx = '/{{@ ?block ?(.*?) ?}}/i';
        $code = preg_replace($regx, '', $code);
        return $code;
    }
}
