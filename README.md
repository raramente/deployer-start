# Deployer starter project

A starter project to deploy WordPress websites using [Deployer](https://deployer.org/). Meant to be used with with [Bedrock](https://roots.io/bedrock/) and [Sage](https://roots.io/sage/) to speed up development.

## What does it do?

- Groups all of the configuration into a single file: ```deploy-config.yaml```
- Changes the provision recipe to install nvm (node), npm and yarn
- Installs composer on the WordPress installation and on the theme
- Adds tasks for managing files
- Adds tasks for managing the database

## Set up

Download this repo and edit:

- deploy-config.yaml - Edit all of the variables according to the needs of the project and your server.

## Provisioning

If you choose to provision the server using deployer this will add a task in order to intalls nvm, npm and yarn to the server. This will be needed to build the [Sage](https://roots.io/sage/) theme.

```bash
dep provision 
```

## Deploying

Runing the deployer deploy command is enough to deploy the website:

```bash
dep deploy
```

A few extra tasks will run in order to properly deploy a Bedrock and Sage website:

- `config:check` - Checks if all the variables are set.
- `deploy:vendors` - Will run composer install on the WordPress bedrock installation
- `deploy:theme:vendors` - Will run composer on the theme
- `deploy:theme:resources` - Will compile the theme assets
- `hangouts:success` or `hangouts:failure` - Will send notifications to a Google Hangouts channel
- `wp:cache:clear` - After deployment is done, all caches will be flushed

## Uploads folder

### Download

Downloads the uploads folder contents from the server to your local machine.

```bash
dep files:download
```

### Upload

Uploads the uploads folder contents from your local machine to the server.

```bash
dep files:upload
```

## Database

### Backup

Backs up the database to `{{local/dump_path}}/{{stage}}/file.sql`.

There is command for local backups and to backup the file from the server:

```bash
dep db:local:backup
dep db:backup
```

### Download

Downloads the database, imports it to your local machine and replaces the server URL for your local URL.

```bash
dep db:download
```

### Upload

Uploads the database, imports it to your local machine and replaces your local URL for the server URL.

```bash
dep db:upload
```