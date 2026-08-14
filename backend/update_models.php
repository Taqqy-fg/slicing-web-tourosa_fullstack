<?php
foreach(glob(__DIR__.'/app/Models/*.php') as $file) {
    $content = file_get_contents($file);
    $content = str_replace('use HasFactory;', "use HasFactory;\n    protected \$guarded = [];", $content);
    file_put_contents($file, $content);
}
echo "Done.";
