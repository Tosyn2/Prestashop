<?php namespace Mds\Prestashop\Helpers;

class View {

	public static function make($path, $data)
	{
		$view = new self;

		return $view->generate($path, $data);
	}

	/**
	 * Get the evaluated contents of the view at the given path.
	 *
	 * @param  string $__path
	 * @param  array  $__data
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function generate($__path, $__data)
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
