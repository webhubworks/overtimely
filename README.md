# overtimely
A simple CLI tool that interacts with the Timely API to fetch the relevant data and calculate your overtime balance from it.

Built with [Laravel Zero](https://laravel-zero.com/).

## Requirements
- PHP ^8.5 
- Composer
- A Timely OAuth application ([details below](#setup))

## Installation
This app is bundled into a standalone PHAR you can download via composer:
```shell
composer global require webhubworks/overtimely
```
Confirm installation:
```shell
overtimely
```

## Setup

Your Timely accounts admin needs to configure a new OAuth2 application with an OOB redirect URI.\
You'll need the following from them to use this tool:
- Application ID (Client ID)
- Application Secret (Client Secret)
- Redirect URI
- Account ID

Once you've received these details, run the following command and follow the instructions:
```shell
overtimely app:setup
```

## Usage

Run `overtimely` to get a list of the available commands and use the `--help` option with a command to see its description, arguments and options.

## Development

### Local development
To run the app locally, you have to prefix it with PHP:

```shell
php overtimely
```

You can also use an `.env` file to set/override the user configuration values which are set by the `app:setup` and `set:*` commands. Have a look at the `.env.example` file to see the available environment variables.

### Releasing a build

This app is distributed as a PHAR, so you need to make a new build including your changes to actually release them.

To facilitate this, the project has a [Makefile](https://opensource.com/article/18/8/what-how-makefile). So after you have committed all of your changes (remember to [update the **changelog**](https://keepachangelog.com/en/1.1.0) as well!), you can run the following:

```shell
make release VERSION=<build_version>
```

This will **build**, **commit**, **tag** and **push** a new PHAR all in one go.

For deciding on the correct `build_version` to use: This project follows the [Semantic Versioning](https://semver.org) format.
