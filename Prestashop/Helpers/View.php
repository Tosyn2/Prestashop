<?php namespace Mds\Prestashop\Helpers;

class View {

	/**
	 * Get the evaluated contents of the view at the given path.
	 *
	 * @param  string $__path
	 * @param  array  $__data
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function make($__path, $__data)
	{
		$__path = _MDS_DIR_ .'/views/'. $__path .'.php';
		$__obLevel = ob_get_level();

		ob_start();

		extract($__data);

		try {
			include $__path;
		} catch (\Exception $e) {
			while (ob_get_level() > $__obLevel) {
				ob_end_clean();
			}

			throw $e;
		}

		return ltrim(ob_get_clean());
	}
}
