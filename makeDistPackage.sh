
#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

mkdir -p "/tmp/googlebridge-dist/"
rsync -avz --exclude ".git" "$DIR/." "/tmp/googlebridge-dist"

rm /tmp/googlebridge-dist/composer.json
rm /tmp/googlebridge-dist/composer.lock
rm /tmp/googlebridge-dist/config.php
rm /tmp/googlebridge-dist/devices.php
rm /tmp/googlebridge-dist/.gitignore
rm /tmp/googlebridge-dist/makeDistPackage.sh
rm -r /tmp/googlebridge-dist/vendor/bluerhinos/phpmqtt/examples/
rm -rf /tmp/googlebridge-dist/dist/

mkdir -p "$DIR/dist"
read -r VERSION < "$DIR/VERSION"

cd /tmp/googlebridge-dist
zip -FS -r $DIR/dist/kGooglebridge-dist-$VERSION.zip .

cd $DIR

echo Created dist-package for version $VERSION

rm -rf "/tmp/googlebridge-dist"