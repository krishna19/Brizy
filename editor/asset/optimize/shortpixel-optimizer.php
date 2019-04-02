<?php

class  Brizy_Editor_Asset_Optimize_ShortpixelOptimizer implements Brizy_Editor_Asset_Optimize_OptimizerInterface {

	/**
	 * @var string
	 */
	private $apiKey;

	/**
	 * Brizy_Editor_Asset_Optimize_OptimizerInterface constructor.
	 *
	 * @param $config
	 */
	public function __construct( $config ) {
		$this->apiKey = $config['API_KEY'];
	}

	/**
	 * @param $sourcePath
	 * @param $targetPath
	 *
	 * @return mixed
	 */
	public function optimize( $sourcePath, $targetPath ) {
		ShortPixel\setKey( $this->apiKey );
		$result = ShortPixel\fromFile( $sourcePath )->optimize(1)->wait( 100 )->toFiles( dirname( $targetPath ) );

		if(count($result->succeeded)==1) {
			return true;
		}

		return false;
	}
}