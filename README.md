# overtimely
A simple CLI tool that interacts with the Timely API to fetch the relevant data and calculate your overtime balance from it.

Built with [Laravel Zero](https://laravel-zero.com/)

## Requirements
- PHP ^8.5 
- Composer
- A Timely OAuth application [see below](#setup)

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
