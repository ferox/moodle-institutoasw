<?php

namespace Moodle\Events;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Installers\MoodleInstaller;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Exception;
use stdClass;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class MoodleScriptEvents
{
    // Constant for the default installer directory
    const INSTALLER_DIR = 'public';

    /**
     * Handles the pre-installation event.
     *
     * @param Event $event The Composer event object.
     * @throws Exception
     */
    public static function preInstall(Event $event): void
    {
        $io = $event->getIO();
        $io->write("------------ preInstall ------------");

        $installerDir = self::getInstallerDir($event);

        // TODO required that folder no exists
        if (is_dir($installerDir) && file_exists($installerDir . "/version.php")) {
            throw new Exception("Moodle is already installed in the folder: $installerDir.");
        }
    }

    /**
     * Handles the post-install event.
     *
     * @param Event $event The Composer event object.
     */
    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();
        $io->write("------------ postInstall ------------");

        // TODO resolve need move or copy Moodle
        self::moveMoodle($event);
        self::copyConfig($event);
    }

    /**
     * Handles the pre-update event.
     *
     * @param Event $event The Composer event object.
     */
    public static function preUpdate(Event $event): void
    {
        $io = $event->getIO();
        $io->write("------------ preUpdate ------------");

        self::copyConfigToRoot($event);
    }

    /**
     * Handles the post-update event.
     *
     * @param Event $event The Composer event object.
     */
    public static function postUpdate(Event $event): void
    {
        $io = $event->getIO();
        $io->write("------------ postUpdate ------------");

        $installerDir = self::getInstallerDir($event);

        if (self::isNewMoodle($event)) {
            self::removeMoodle($event);
            self::moveMoodle($event);
            self::copyConfig($event);
            $io->write("<warning>DANGER! Run 'composer update' to reinstall plugins.</warning>");
        }

        if (file_exists("$installerDir/config.php")) {
            self::cleanCache($event);
        }
    }

    /**
     * Handles the pre-update-package event.
     *
     * @param PackageEvent $event The Composer package event object.
     */
    public static function preUpdatePackage(PackageEvent $event): void
    {
        $io = $event->getIO();
        $io->write("------------ preUpdatePackage ------------");

        $package = self::getPackage($event);
        if (isset($package) && $package instanceof PackageInterface) {
            $io->write("Updating package ", FALSE);
            $io->write($package->getName());
        }
    }

    /**
     * Handles the post-package event.
     *
     * @param PackageEvent $event The Composer package event object.
     */
    public static function postPackage(PackageEvent $event): void
    {
        $io = $event->getIO();
        $io->write("------------ postPackage ------------");

        $installerDir = self::getInstallerDir($event);

        $package = self::getPackage($event);

        if (!self::isMoodle($package)) {
            $packageType = $package->getType();
            if (str_starts_with($packageType, 'moodle-')) {
                if (!self::existsInstallerPath($event, $packageType)) {
                    $pluginType = str_replace('moodle-', '', $packageType);

                    $moodleInstaller = new MoodleInstaller();
                    $locations = $moodleInstaller->getLocations();
                    if (isset($locations[$pluginType])) {
                        $appDir = getcwd();
                        $path = $event->getComposer()->getInstallationManager()->getInstallPath($package);
                        $currentPath = $appDir . DIRECTORY_SEPARATOR . $path;
                        $newPath = $appDir . DIRECTORY_SEPARATOR . $installerDir . '/' . $path;

                        try {
                            $filesystem = new Filesystem();
                            $filesystem->copyThenRemove($currentPath, $newPath);

                            while ($currentPath !== $appDir) {
                                if (is_dir($currentPath)) {
                                    if (count(scandir($currentPath)) == 2) {
                                        rmdir($currentPath);
                                    }
                                }
                                $paths = explode(DIRECTORY_SEPARATOR, $currentPath);
                                array_pop($paths);
                                $currentPath = implode(DIRECTORY_SEPARATOR, $paths);
                            }
                        } catch (Exception $exception) {
                            $io->error($exception->getMessage());
                        }
                    }
                }
            }
        }

        if (isset($package) && $package instanceof PackageInterface) {
            $installationManager = $event->getComposer()->getInstallationManager();
            $path = $installationManager->getInstallPath($package);
            if (file_exists("$path/.gitmodules")) {
                $packageName = $package->getName();
                $io->write("This package $packageName own Submodules Git and they will install now");
                exec("cd $path && git submodule update --init");
            }
        }
    }

    /**
     * Retrieves the package associated with the Composer event.
     *
     * @param PackageEvent $event The Composer package event object.
     * @return PackageInterface|null The package associated with the event.
     */
    public static function getPackage(PackageEvent $event): ?PackageInterface
    {
        $package = new stdClass();
        $operation = $event->getOperation();

        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } else if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
        }

        return $package;
    }

    /**
     * copyConfigToRoot
     *
     * Copies the "config.php" file from the installation directory to the root directory.
     *
     * @param Event $event The Composer event.
     */
    public static function copyConfigToRoot(Event $event): void
    {
        if (!self::canCopyConfig($event)) {
            return;
        }

        $io = $event->getIO();
        $appDir = getcwd();

        $installerDir = self::getInstallerDir($event);

        if (file_exists("$installerDir/config.php")) {
            $io->write("Copying $installerDir/config.php to ROOT/");
            if (!copy("$appDir/$installerDir/config.php", "$appDir/config.php")) {
                $io->write("FAILURE");
            }
        } else {
            $io->write("File $installerDir/config.php not found!");
        }
    }

    /**
     * moveMoodle
     *
     * Copies the "vendor/moodle/moodle" directory to the installation directory.
     *
     * @param Event $event The Composer event.
     */
    public static function moveMoodle(Event $event): void
    {
        $io = $event->getIO();
        $appDir = getcwd();

        $installerDir = self::getInstallerDir($event);

        $filesystem = new Filesystem();
        $io->write("Copying vendor/moodle/moodle to $installerDir/");
        $filesystem->copyThenRemove($appDir . "/vendor/moodle/moodle", $appDir . DIRECTORY_SEPARATOR . $installerDir);
    }

    /**
     * removeMoodle
     *
     * Removes the Moodle installation directory.
     *
     * @param Event $event The Composer event.
     */
    public static function removeMoodle(Event $event): void
    {
        $io = $event->getIO();

        $installerDir = self::getInstallerDir($event);

        if (is_dir($installerDir)) {
            $io->write("Removing $installerDir/");
            self::deleteRecursive($installerDir);
        }
    }

    /**
     * copyConfig
     *
     * Copies the "config.php" file from the root directory to the installation directory.
     *
     * @param Event $event The Composer event.
     */
    public static function copyConfig(Event $event): void
    {
        if (!self::canCopyConfig($event)) {
            return;
        }

        $io = $event->getIO();
        $appDir = getcwd();

        $installerDir = self::getInstallerDir($event);

        if (file_exists('config.php')) {
            $io->write("Copying config.php to $installerDir/");
            if (!copy("$appDir/config.php", "$appDir/$installerDir/config.php")) {
                $io->write("FAILURE");
            }
        }
    }

    /**
     * setMaintenance
     *
     * Enables or disables Moodle maintenance mode.
     *
     * @param Event $event The Composer event.
     * @param boolean $status Indicates whether maintenance mode should be enabled (true) or disabled (false). Default is false.
     */
    public static function setMaintenance(Event $event, bool $status = false): void
    {
        $io = $event->getIO();
        $appDir = getcwd();

        $installerDir = self::getInstallerDir($event);

        if ($status) {
            $io->write("Enabling Maintenance Mode");
            exec("php $appDir/$installerDir/admin/cli/maintenance.php --enable");
        } else {
            $io->write("Disabling Maintenance Mode");
            exec("php $appDir/$installerDir/admin/cli/maintenance.php --disable");
        }
    }

    /**
     * cleanCache
     *
     * Clears the Moodle cache.
     *
     * @param Event $event The Composer event.
     */
    public static function cleanCache(Event $event): void
    {
        if (!self::canCopyConfig($event)) {
            return;
        }

        $io = $event->getIO();
        $appDir = getcwd();

        $installerDir = self::getInstallerDir($event);

        $io->write("Clearing the Moodle cache");
        exec("php $appDir/$installerDir/admin/cli/purge_caches.php");
    }

    /**
     * isNewMoodle
     *
     * Checks if a new Moodle version is detected.
     *
     * @param Event $event The Composer event.
     * @return boolean Returns true if a new Moodle version is detected, false otherwise.
     */
    public static function isNewMoodle(Event $event): bool
    {
        define("MOODLE_INTERNAL", true);
        define('MATURITY_ALPHA', 50);
        define('MATURITY_BETA', 100);
        define('MATURITY_RC', 150);
        define('MATURITY_STABLE', 200);
        define('ANY_VERSION', 'any');

        $io = $event->getIO();
        $appDir = getcwd();

        $installerDir = self::getInstallerDir($event);

        $oldVersion = 0;
        $newVersion = 0;

        $oldFile = $appDir . "/" . $installerDir . "/version.php";
        if (file_exists($oldFile)) {
            require_once $oldFile;
            if (isset($version)) {
                $oldVersion = $version;
            }
        } else {
            return false;
        }

        $newFile = $appDir . "/vendor/moodle/moodle/version.php";
        if (file_exists($newFile)) {
            require_once $newFile;
            if (isset($version)) {
                $newVersion = $version;
            }
        } else {
            return false;
        }

        if ($newVersion > $oldVersion) {
            $io->write("### NEW MOODLE VERSION DETECTED ###");
            return true;
        }

        return false;
    }

    /**
     * deleteRecursive
     *
     * Recursively deletes a file or directory.
     *
     * @param string $path The path to the file or directory to delete.
     * @return boolean Returns true on success, false on failure.
     */
    public static function deleteRecursive(string $path): bool
    {
        if (is_file($path) || is_link($path)) {
            return unlink($path);
        }
        $success = true;
        $dir = dir($path);
        while (($entry = $dir->read()) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $entry_path = $path . '/' . $entry;
            $success = static::deleteRecursive($entry_path) && $success;
        }
        $dir->close();
        return rmdir($path) && $success;
    }

    /**
     * Retrieves the installer directory from composer.json's extra configuration.
     *
     * @param Event|PackageEvent $event The Composer event object.
     * @return string The installer directory.
     */
    public static function getInstallerDir(PackageEvent|Event $event): string
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        return $extra['installerdir'] ?? self::INSTALLER_DIR;
    }

    /**
     * Checks if an installer path exists for a given package type.
     *
     * @param Event|PackageEvent $event The Composer event object.
     * @param string $packageType The package type to check.
     * @return bool True if the installer path exists, false otherwise.
     */
    public static function existsInstallerPath(PackageEvent|Event $event, string $packageType): bool
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        return isset($extra['installer-paths'][$packageType]);
    }

    /**
     * Checks if a given package is Moodle.
     *
     * @param PackageInterface $package The package to check.
     * @return bool True if the package is Moodle, false otherwise.
     */
    public static function isMoodle(PackageInterface $package): bool
    {
        if ($package->getName() === 'moodle/moodle') {
            return true;
        }
        return false;
    }

    /**
     * canCopyConfig
     *
     * @param Event $event The Composer event.
     */
    public static function canCopyConfig(Event $event): bool
    {
        $thisConfig = $event->getComposer()->getConfig()->get('moodle-composer');

        if (isset($thisConfig['copy-config']) && $thisConfig['copy-config'] === false) {
            return false;
        }

        return true;
    }

    /**
     * canClearCache
     *
     * @param Event $event The Composer event.
     */
    public static function canClearCache(Event $event): bool
    {
        $thisConfig = $event->getComposer()->getConfig()->get('moodle-composer');

        if (isset($thisConfig['clear-cache']) && $thisConfig['clear-cache'] === false) {
            return false;
        }

        return true;
    }
}
