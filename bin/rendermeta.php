#!/usr/bin/php
<?php
if ('cli' != php_sapi_name()) die();

#------------------------------------------------------------------------------
ini_set('memory_limit','128M');
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../').'/');
require_once DOKU_INC.'inc/init.php';
require_once DOKU_INC.'inc/common.php';
require_once DOKU_INC.'inc/search.php';
require_once DOKU_INC.'inc/cliopts.php';

#------------------------------------------------------------------------------
function usage() {
    print "Usage: rendermeta.php [wiki:namespace]

    Renders all pages in wiki or namespace using the metadata renderer
    This will update all cotent that uses this stage as its trigger (data plugin)

    If the optional [wiki:namespace] is not provided,
    defaults to the root wiki namespace

    OPTIONS
        -h, --help get help
";
}

#------------------------------------------------------------------------------
define ('DW_DIR_CONTINUE',1);
define ('DW_DIR_NS',2);
define ('DW_DIR_PAGE',3);

#------------------------------------------------------------------------------
function dw_dir_filter($entry, $basepath) {
    if ($entry == '.' || $entry == '..' ) {
        return DW_DIR_CONTINUE;
    }
    if ( is_dir($basepath . '/' . $entry) ) {
        if ( strpos($entry, '_') === 0 ) {
            return DW_DIR_CONTINUE;
        }
        return DW_DIR_NS;
    }
    if ( preg_match('/\.txt$/',$entry) ) {
        return DW_DIR_PAGE;
    }
    return DW_DIR_CONTINUE;
}

#------------------------------------------------------------------------------
function dw_get_pages($dir) {
    static $trunclen = NULL;
    if ( !$trunclen ) {
        global $conf;
        $trunclen = strlen($conf['datadir'].':');
    }

    if ( !is_dir($dir) ) {
        fwrite( STDERR, "Unable to read directory $dir\n");
        exit(1);
    }

    $pages = array();
    $dh = opendir($dir);
    while ( false !== ( $entry = readdir($dh) ) ) {
        $status = dw_dir_filter($entry, $dir);
        if ( $status == DW_DIR_CONTINUE ) {
            continue;
        } else if ( $status == DW_DIR_NS ) {
            $pages = array_merge($pages, dw_get_pages($dir . '/' . $entry));
        } else {
            $page = array(
                'id'  => pathID(substr($dir.'/'.$entry,$trunclen)),
                'file'=> $dir.'/'.$entry,
                );
            $pages[] = $page;
        }
    }
    closedir($dh);
    return $pages;
}

#------------------------------------------------------------------------------
function dw_internal_links($page) {
    global $conf;
    $instructions = p_get_instructions(file_get_contents($page['file']));
    $links = array();
    $cns = getNS($page['id']);
    $exists = false;
    foreach($instructions as $ins){
        if($ins[0] == 'internallink' || ($conf['camelcase'] && $ins[0] == 'camelcaselink') ){
            $mid = $ins[1][0];
            resolve_pageid($cns,$mid,$exists);
            if ( !$exists ) {
								list($mid) = explode('#',$mid); //record pages without hashs
                $links[] = $mid;
            }
        }
    }
    return $links;
}

#------------------------------------------------------------------------------
$OPTS = Doku_Cli_Opts::getOptions(__FILE__,'h',array('help'));

if ( $OPTS->isError() ) {
    fwrite( STDERR, $OPTS->getMessage() . "\n");
    exit(1);
}

if ( $OPTS->has('h') or $OPTS->has('help') ) {
    usage();
    exit(0);
}

$START_DIR = $conf['datadir'];

if ( $OPTS->numArgs() == 1 ) {
    $START_DIR .= '/' . $OPTS->arg(0);
}

#------------------------------------------------------------------------------
foreach ( dw_get_pages($START_DIR) as $WIKI_PAGE ) {
  
    $meta = p_read_metadata($WIKI_PAGE['id'], true);
    $meta = p_render_metadata($WIKI_PAGE['id'], $meta);
    io_saveFile(metaFN($id, '.meta'), serialize($meta));
    echo $WIKI_PAGE['id']."\n";
}
exit(0);
