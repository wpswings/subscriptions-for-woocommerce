#!/usr/bin/env bash
# Install the WordPress test library.
#
# Usage:
#   bash tests/bin/install-wp-tests.sh <db-name> <db-user> <db-pass> \
#       [db-host|socket] [wp-version] [skip-database-creation]
#
# Example (Local by Flywheel with Unix socket):
#   bash tests/bin/install-wp-tests.sh local root root \
#       "/home/shivam/.config/Local/run/wCdL6ejyZ/mysql/mysqld.sock" latest true

set -ex

DB_NAME=${1:-wordpress_test}
DB_USER=${2:-root}
DB_PASS=${3:-root}
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}
SKIP_DB_CREATE=${6:-false}

WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress}

download() {
    if [ "$(command -v curl)" ]; then
        curl -s "$1" > "$2"
    elif [ "$(command -v wget)" ]; then
        wget -nv -O "$2" "$1"
    fi
}

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
    WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
    WP_TESTS_TAG="trunk"
else
    download https://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
    LATEST_VERSION=$(grep -o '"version":"[^"]*"' /tmp/wp-latest.json | sed 's/"version":"//;s/"//')
    if [[ $WP_VERSION == 'latest' ]]; then
        WP_VERSION=$LATEST_VERSION
    fi
    WP_TESTS_TAG="tags/$WP_VERSION"
fi

set -ex

install_wp() {
    if [ -d "$WP_CORE_DIR" ]; then
        return
    fi
    mkdir -p "$WP_CORE_DIR"
    if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        mkdir -p /tmp/wordpress-nightly && download https://wordpress.org/nightly-builds/wordpress-latest.zip /tmp/wordpress-nightly/wordpress-nightly.zip
        unzip -q /tmp/wordpress-nightly/wordpress-nightly.zip -d /tmp/wordpress-nightly/
        mv /tmp/wordpress-nightly/wordpress/* "$WP_CORE_DIR"
    else
        if [ "$WP_VERSION" == 'latest' ]; then
            local ARCHIVE_NAME='latest'
        elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
            local ARCHIVE_NAME="wordpress-$WP_VERSION"
        fi
        download https://wordpress.org/${ARCHIVE_NAME}.tar.gz /tmp/wordpress.tar.gz
        tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"
    fi
}

install_test_suite() {
    if [ -d "$WP_TESTS_DIR" ]; then
        return
    fi
    mkdir -p "$WP_TESTS_DIR"

    # Derive the GitHub branch/tag ref from WP_TESTS_TAG.
    # WP_TESTS_TAG is one of: "trunk", "branches/X.Y", "tags/X.Y.Z"
    if [[ $WP_TESTS_TAG == "trunk" ]]; then
        GH_REF="trunk"
    elif [[ $WP_TESTS_TAG =~ ^branches/(.+)$ ]]; then
        GH_REF="${BASH_REMATCH[1]}"
    elif [[ $WP_TESTS_TAG =~ ^tags/(.+)$ ]]; then
        GH_REF="${BASH_REMATCH[1]}"
    else
        GH_REF="$WP_VERSION"
    fi

    TMP_REPO="/tmp/wordpress-develop-sparse"
    rm -rf "$TMP_REPO"

    # Sparse clone — only fetches tree objects, not blobs, then checks out
    # tests/phpunit/includes and tests/phpunit/data.
    git clone \
        --depth=1 \
        --filter=blob:none \
        --sparse \
        --branch "$GH_REF" \
        https://github.com/WordPress/wordpress-develop.git \
        "$TMP_REPO"

    git -C "$TMP_REPO" sparse-checkout set \
        "tests/phpunit/includes" \
        "tests/phpunit/data"

    cp -r "$TMP_REPO/tests/phpunit/includes" "$WP_TESTS_DIR/includes"
    cp -r "$TMP_REPO/tests/phpunit/data"     "$WP_TESTS_DIR/data"
    rm -rf "$TMP_REPO"

    download \
        "https://raw.githubusercontent.com/WordPress/wordpress-develop/${GH_REF}/wp-tests-config-sample.php" \
        "$WP_TESTS_DIR/wp-tests-config.php"

    sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/yourusernamehere/$DB_USER/"        "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/yourpasswordhere/$DB_PASS/"        "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|localhost|$DB_HOST|"               "$WP_TESTS_DIR/wp-tests-config.php"
}

install_db() {
    if [ "$SKIP_DB_CREATE" = "true" ]; then
        return
    fi
    mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" 2>/dev/null || true
}

install_wp
install_test_suite
install_db
