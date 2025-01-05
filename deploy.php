<?php
/**
 * Re-usable deployer file for WordPress projects.
 * Deployer 8.x
 * php version 8.1
 * 
 * @category Deployer
 * @package WordPress
 * @author Hugo Silva <hugo@hugosilva.me>
 * @license https://opensource.org/license/mit The MIT License
 * @version GIT: @1.1.0@
 * @link https://github.com/raramente/deployer-start
 * 
 */
namespace Deployer;

use Deployer\Exception\ConfigurationException;

require 'recipe/wordpress.php';
require 'contrib/hangouts.php'; // https://deployer.org/docs/8.x/contrib/hangouts

import("deploy-config.yaml");

/**
 * Config tasks
 */
task('config:check', function() {
    if ( !has('repository') ) {
        throw new ConfigurationException('Please define a git repository.');
    }
    if ( !has('keep_releases') ) {
        throw new ConfigurationException('Please define the releases to keep.');
    }
    if ( !has('theme/name') ) {
        throw new ConfigurationException('Please define the theme name.');
    }
    if ( !has('theme/path') ) {
        throw new ConfigurationException('Please define the theme path.');
    }
    if ( !has('local/url') ) {
        throw new ConfigurationException('Please define the local URL.');
    }
    if ( !has('local/path_to_wp_root') ) {
        throw new ConfigurationException('Please define the local path to root.');
    }
    if ( !has('local/wp') ) {
        throw new ConfigurationException('Please define the local WP-CLI path.');
    }
    if ( !has('bin/php') ) {
        throw new ConfigurationException('Please define the path to php.');
    }
    if ( !has('bin/composer') ) {
        throw new ConfigurationException('Please define the path to composer.');
    }
})->desc('Checks if all the configuration is set.');

/**
 * Deploy tasks
 */
task('deploy:vendors', function() {
    run("cd {{release_path}} && {{bin/composer}} install --no-ansi --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts --classmap-authoritative");
})->desc('Installs bedrock composer dependencies.');

task('deploy:theme:vendors', function() {
    run("cd {{theme/path}}/{{theme/name}} && {{bin/composer}} install --no-ansi --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts --classmap-authoritative");
})->desc('Install sage composer');

task('deploy:theme:resources', function() {
    run("cd {{theme/path}}/{{theme/name}} && {{bin/yarn}} install");
    run("cd {{theme/path}}/{{theme/name}} && {{bin/yarn}} build");
})->desc('Install sage composer');

task('wp:cache:clear', function() {
    run( "cd {{current_path}} && {{bin/wp}} cache flush" );
})->desc('Clears WordPress cache');

/**
 * File tasks
 */
task('files:upload', function() {
    upload( "./{{local/path_to_wp_root}}/{{local/wp_uploads}}/", "{{deploy_path}}/shared/{{local/wp_uploads}}" );
})->desc('Send the uploads folder to the server.');

task('files:download', function() {
    download( "{{deploy_path}}/shared/{{local/wp_uploads}}/", "./{{local/path_to_wp_root}}/{{local/wp_uploads}}" );
})->desc('Send the uploads to the server.');

/**
 * Database tasks
 */
task('db:local:backup', function() {
    $filename = 'local_' . date('Y-m-d_H-i-s') . '.sql';
    runLocally('mkdir -p ./{{local/dump_path}}/local');
    runLocally('{{local/wp}} db export ' . $filename);
    runLocally('mv ./{{local/path_to_wp_root}}/' . $filename . ' ./{{local/dump_path}}/local/' . $filename);
})->desc('Backup local database.');

task('db:backup', function() {
    $filename = get('labels')['stage'] . '_' . date('Y-m-d_H-i-s') . '.sql';
    run('cd {{current_path}} && {{bin/wp}} db export ' . $filename);
    runLocally("mkdir -p ./{{local/dump_path}}/" . get("labels")['stage']);
    download("{{current_path}}/" . $filename, "./{{local/dump_path}}/" . get("labels")['stage'] . "/" . $filename);
    run("rm {{current_path}}/" . $filename);
})->desc('Backup server database.');

task('db:download', function() {
    // Download database from the server.
    $filename = get('labels')['stage'] . '_' . date('Y-m-d_H-i-s') . '.sql';
    run('cd {{current_path}} && {{bin/wp}} db export ' . $filename);
    runLocally("mkdir -p ./{{local/dump_path}}/" . get("labels")['stage']);
    download("{{current_path}}/" . $filename, "./{{local/dump_path}}/" . get("labels")['stage'] . "/" . $filename);
    run("rm {{current_path}}/" . $filename);

    // Move the file to the WP root so lando can read it.
    runLocally( "mv ./{{local/dump_path}}/" . get("labels")['stage'] . "/" . $filename . " ./{{local/path_to_wp_root}}/" . $filename );
    runLocally( "{{local/wp}} db import " . $filename );
    runLocally( "rm ./{{local/path_to_wp_root}}/" . $filename ); // Remove the file
    // Replace the URLs.
    runLocally( "{{local/wp}} search-replace {{public_url}} {{local/address}}" );
})->desc("Downloads and imports database.");

task('db:upload', function() {
    // Backup local database.
    $filename = 'local_' . date('Y-m-d_H-i-s') . '.sql';
    runLocally('mkdir -p ./{{local/dump_path}}/local');
    runLocally('{{local/wp}} db export ' . $filename);
    runLocally('mv ./{{local/path_to_wp_root}}/' . $filename . ' ./{{local/dump_path}}/local/' . $filename);

    // Upload file to the server
    upload( "./{{local/dump_path}}/local/" . $filename, "{{current_path}}/" . $filename );

    // Import the database
    run( "cd {{current_path}} && {{bin/wp}} db import " . $filename );

    // Remove lost file.
    run( "rm {{current_path}}/" . $filename );

    // Replace the URLs.
    run( "cd {{current_path}} && {{bin/wp}} search-replace {{local/address}} {{public_url}}" );
})->desc("Downloads and imports database.");

/**
 * Messaging tasks
 */
task('hangouts:success', function() {
    if ( !get('notifications')['enabled'] ) {
        task('chat:notify:success')->disable();
        return;
    }
    $releases = get('releases_log');
    set( 'chat_webhook', get('notifications')['webhook'] );
    set( 'stage', get('labels')['stage'] );
    set( 'chat_title', get('name') );
    set( 'chat_subtitle', 'Deploy successful' );
    set( 'chat_favicon', get('notifications')['favicon'] );
    set( 'chat_line1', get('branch') . ' has been deployed to ' . get('labels')['stage'] );
    set( 'chat_line2', end( $releases )['user'] );
})->desc('Sends a success message to hangouts.');

task('hangouts:failure', function() {
    if ( !get('notifications')['enabled'] ) {
        task('chat:notify:success')->disable();
        return;
    }
    $releases = get('releases_log');
    set( 'chat_webhook', get('notifications')['webhook'] );
    set( 'stage', get('labels')['stage'] );
    set( 'chat_title', get('name') );
    set( 'chat_subtitle', 'Deploy failure' );
    set( 'chat_favicon', get('notifications')['favicon'] );
    set( 'chat_line1', get('branch') . ' failed deploy to ' . get('labels')['stage'] );
    set( 'chat_line2', end( $releases )['user'] );
})->desc('Sends a failure message to hangouts.');


/**
 * Add the tasks to the flow
 */
// Run composer install
before('deploy', 'config:check');
after('deploy:update_code', 'deploy:vendors');
// Run composer install, yarn install && yarn build
after('deploy:vendors', 'deploy:theme:vendors');
after('deploy:theme:vendors', 'deploy:theme:resources');

after('deploy:success', 'wp:cache:clear');

// Sets up the variables and sends the chat notification.
after('deploy:success', 'hangouts:success');
after('hangouts:success', 'chat:notify:success');




// If deploy fails automatically unlock.
after( 'deploy:failed', 'deploy:unlock' );
// Sets up the variables and sends the chat notification.
after( 'deploy:failed', 'hangouts:failure' );
after('hangouts:failure', 'chat:notify:failure');


/**
 * Provision tasks
 */
task('provision:install:node', function() {
    run('curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash && source ~/.bashrc && source ~/.nvm/nvm.sh && nvm install 22 && corepack enable yarn');
})->desc("Installs node, npm and yarn on the server.");

// Installs node, npm and yarn on provision
before('provision', 'config:check');
after('provision:install', 'provision:install:node');
