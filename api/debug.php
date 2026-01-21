<?php
header('Content-Type: text/plain');

function listFolderFiles($dir){
    echo "Listing $dir:\n";
    if(!is_dir($dir)){
        echo "$dir is not a directory or does not exist.\n\n";
        return;
    }
    $ffs = scandir($dir);

    unset($ffs[array_search('.', $ffs, true)]);
    unset($ffs[array_search('..', $ffs, true)]);

    // prevent empty ordered elements
    if (count($ffs) < 1)
        return;

    foreach($ffs as $ff){
        echo $ff;
        if(is_dir($dir.'/'.$ff)) echo " [DIR]";
        echo "\n";
    }
    echo "\n";
}

echo "Current DIR: " . __DIR__ . "\n\n";
listFolderFiles(__DIR__);
listFolderFiles(__DIR__ . '/lib');
listFolderFiles(__DIR__ . '/../assets');
listFolderFiles(__DIR__ . '/admin');
?>
