# Theme-Compiler

The contao theme compiler bundle adds the functionality to compile selected scss files within your theme settings.

+ [Features](#features)
+ [How to install](#how-to-install-the-package)
+ [Initial setup](#initial-setup)

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


5. Compile in your theme-settings, within your theme overview or under maintenance

   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-theme-compiler-bundle/themeOverview.jpg)
   ![Admin View: Advanced form overview](https://www.oveleon.de/share/github-assets/contao-theme-compiler-bundle/maintenanceSettings.jpg)