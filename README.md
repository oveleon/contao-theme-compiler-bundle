# Theme-Compiler

The contao theme compiler bundle adds the functionality to compile selected scss files within your theme settings.

+ [Features](#features)
+ [How to install](#how-to-install-the-package)
+ [Initial setup](#initial-setup)
+ [Console commands](#console-commands)
+ [Miscellaneous](#miscellaneous)

## Features

- Compiles your scss files in your contao installation
- A compile button next to your themes
- Able to add multiple source files
- Able to compile multiple files into one
- Enable backups for your compiled scss

## How to install the package

#### Via composer

```
composer require oveleon/contao-theme-compiler-bundle
```

#### Via contao-manager

```
Search for contao theme compiler bundle and add it to your extensions.
```

## Initial setup

1. Create a theme and add source file/s in your theme settings


2. Add a destination folder for your source files


3. (Optional settings for combining your files and more)


4. Save

   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-theme-compiler-bundle/themeSettings.jpg)


5. Compile in your theme-settings, within your theme overview, under maintenance or via console command

   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-theme-compiler-bundle/themeOverview.jpg)
   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-theme-compiler-bundle/maintenanceSettings.jpg)

   ```
   php vendor/bin/contao-console contao:themecompiler:compile [id]
   ```

## Console commands

### List themes

- Outputs a list of your themes within ``tl_theme``

```
php vendor/bin/contao-console contao:themecompiler:list
```

### Compile theme

- Compiles a theme ([id] is mandatory):

```
php vendor/bin/contao-console contao:themecompiler:compile [id]
```

## Miscellaneous

### Enable file sync on every compilation

Version 1.8 has been rewritten to skip the DBAFS sync if the files already exist. This saves a database call and logic
for each output file and improves compilation time.

To enable the old behaviour, use following in your config.yaml

```yaml
# config/config.yml
contao_theme_compiler:
  file_sync: true
```
