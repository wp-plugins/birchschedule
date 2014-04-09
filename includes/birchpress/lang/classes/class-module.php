<?php

if(!class_exists('Birchpress_Module')) {

	abstract class Birchpress_Module {
		private $packages;

		protected function __construct() {
			$this->packages = array();
		}

		public function define_interfaces() {
			foreach($this->packages as $package) {
				$package->define_interface();
			}
		}

		public function define_lookups_tables() {
			foreach($this->packages as $package) {
				$package->define_lookups_table();
			}
		}

		public function init() {
			foreach($this->packages as $package) {
				$package->init();
			}
		}

		protected function add_package($package) {
			$this->packages[] = $package;
		}

		public function require_modules() {
			return array();
		}

	}
}
