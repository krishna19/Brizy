<?php

interface Brizy_Editor_Asset_Optimize_OptimizerInterface {

	/**
	 * Brizy_Editor_Asset_Optimize_OptimizerInterface constructor.
	 *
	 * @param $config
	 */
	public function __construct( $config );

	/**
	 * @param $sourcePath
	 * @param $targetPath
	 *
	 * @return mixed
	 */
	public function optimize( $sourcePath, $targetPath );
}