<?php

require_once "wordless_preprocessor.php";

/**
 * Compile LESS files using the `lessc` executable.
 *
 * LessPreprocessor relies on some preferences to work:
 * - css.lessc_path (defaults to "/usr/bin/lessc"): the path to the lessc executable
 * - css.output_style (defaults to "compressed"): the output style used to render css files
 *   (check LESS source for more details: https://github.com/cloudhead/less.js/blob/master/bin/lessc)
 *
 * You can specify different values for this preferences using the Wordless::set_preference() method.
 *
 * @copyright welaika &copy; 2011 - MIT License
 * @see WordlessPreprocessor
 */
class LessPreprocessor extends WordlessPreprocessor {

  public function __construct() {
    parent::__construct();

    $this->set_preference_default_value("css.lessc_path", "/usr/bin/lessc");
    $this->set_preference_default_value("css.output_style", "compress");
  }

  /**
   * Overrides WordlessPreprocessor::asset_hash()
   * @attention This is raw code. Right now all we do is find all the *.{sass,scss} files, concat
   * them togheter and generate an hash. We should find exacty the sass files required by
   * $file_path asset file.
   */
  protected function asset_hash($file_path) {
    $hash = array(parent::asset_hash($file_path));
    $base_path = dirname($file_path);
    $files = $this->folder_tree(dirname($base_path), "*.less");
    sort($files);
    $hash_seed = array();
    foreach ($files as $file) {
      $hash_seed[] = $file . date("%U", filemtime($file));
    }
    return md5(join($hash_seed));
  }

  /**
   * Overrides WordlessPreprocessor::comment_line()
   */
  protected function comment_line($line) {
    return "/* $line */\n";
  }

  /**
   * Overrides WordlessPreprocessor::content_type()
   */
  protected function content_type() {
    return "text/css";
  }

  /**
   * Overrides WordlessPreprocessor::die_with_error()
   */
  protected function die_with_error($description) {
    echo "/************************\n";
    echo $description;
    echo "************************/\n\n";
    echo sprintf(
      'body::before { content: "%s"; font-family: monospace; white-space: pre; display: block; background: #eee; padding: 20px; }',
      'Damn, we\'re having problems compiling the Less. Check the CSS source code for more infos!'
    );
    die();
  }


  /**
   * Process a file, executing lessc executable.
   *
   * Execute the lessc executable, overriding the no-op function inside
   * WordlessPreprocessor.
   *
   * If using php-fpm, remember to pass the PATH environment variable
   * in php-fpm.ini (e.g. env[PATH]=/usr/local/bin:/usr/bin:/bin)
   */
  protected function process_file($file_path, $result_path, $temp_path) {

    $this->validate_executable_or_die($this->preference("css.lessc_path"));

    // On cache miss, we build the file from scratch
    $pb = new ProcessBuilder(array(
      $this->preference("css.lessc_path"),
      $file_path
    ));

    // Since the official lessc executable relies on node.js, we need to
    // inherit env to get access to $PATH so we can find the node executable
    $pb->inheritEnvironmentVariables();

    $proc = $pb->getProcess();
    $code = $proc->run();

    if (0 < $code) {
      $this->die_with_error($proc->getErrorOutput());
    }

    return $proc->getOutput();
  }

  /**
   * Overrides WordlessPreprocessor::supported_extensions()
   */
  public function supported_extensions() {
    return array("less");
  }


  /**
   * Overrides WordlessPreprocessor::to_extension()
   */
  public function to_extension() {
    return "css";
  }

}

