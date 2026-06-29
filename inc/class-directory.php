<?php

/**
 * Wraps directory functions in PHP.
 *
 * @since 3.4.0
 */
class BackupPilot_Directory extends DirectoryIterator {

    /**
     * Creates the iterator.
     *
     * Fixes the path before calling the parent constructor.
     *
     * @param string $path
     */
    public function __construct( $path ) {
        parent::__construct( BackupPilot_Path_Fixer::fix_path( $path ) );
    }

}
