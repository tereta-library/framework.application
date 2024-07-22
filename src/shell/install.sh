#!/bin/bash

rootDir=$(realpath "$(dirname "$0")/../../../../..")

echo "Root directory is: $rootDir"
echo "Creating initial directories"

mkdir -p "$rootDir/var"
chmod 0777 "$rootDir/var"

mkdir -p "$rootDir/var/pids"
chmod 0777 "$rootDir/var/pids"

mkdir -p "$rootDir/pub"
chmod 0744 "$rootDir/pub"

mkdir -p "$rootDir/app"
chmod 0744 "$rootDir/app"

mkdir -p "$rootDir/app/module"
chmod 0744 "$rootDir/app/module"

mkdir -p "$rootDir/app/etc"
chmod 0744 "$rootDir/app/etc"

mkdir -p "$rootDir/app/view"
chmod 0744 "$rootDir/app/view"

if [ "$1" == "link" ]; then
  echo "Linking initial files"
  rm -f "$rootDir/pub/index.php"
  rm -f "$rootDir/pub/.htaccess"
  rm -f "$rootDir/cli.php"

  ln -s "$rootDir/vendor/tereta/framework.application/src/shell/files/pubIndex.php" "$rootDir/pub/index.php"
  ln -s "$rootDir/vendor/tereta/framework.application/src/shell/files/pubHtaccess" "$rootDir/pub/.htaccess"
  ln -s "$rootDir/vendor/tereta/framework.application/src/shell/files/cli.php" "$rootDir/cli.php"
else
  echo "Copying initial files"
  rm -f "$rootDir/pub/index.php"
  rm -f "$rootDir/pub/.htaccess"
  rm -f "$rootDir/cli.php"

  cp "$rootDir/vendor/tereta/framework.application/src/shell/files/pubIndex.php" "$rootDir/pub/index.php"
  cp "$rootDir/vendor/tereta/framework.application/src/shell/files/pubHtaccess" "$rootDir/pub/.htaccess"
  cp "$rootDir/vendor/tereta/framework.application/src/shell/files/cli.php" "$rootDir/cli.php"
fi

echo "Finished."