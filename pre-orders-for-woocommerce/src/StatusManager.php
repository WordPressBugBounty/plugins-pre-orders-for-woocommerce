<?php

namespace Woocommerce_Preorders;

class StatusManager {
	/**
	 * @var mixed
	 */
	private $statusName;
	/**
	 * @var mixed
	 */
	private $label;
	/**
	 * @var mixed
	 */
	private $public = true;
	/**
	 * @var mixed
	 */
	private $showInAdminStatusList = true;
	/**
	 * @var mixed
	 */
	private $showInAdminAllList = true;
	/**
	 * @var mixed
	 */
	private $excludeFromSearch = false;
	/**
	 * @var mixed
	 */
	private $labelCount;

	/**
	 * @param $args
	 */
	public function __construct( $args ) {
		array_walk( $args, function ( $value, $key ) {
			$this->{"$key"} = $value;
		} );

		$this->setStatusName( $args['statusName'] );
	}

	/**
	 * Get the value of statusName.
	 */
	public function getStatusName() {
		return $this->statusName;
	}

	/**
	 * Set the value of statusName.
	 *
	 * @return self
	 */
	public function setStatusName( $statusName ) {
		$this->statusName = 'wc-' . $statusName;

		return $this;
	}

	/**
	 * Get the value of label.
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Set the value of label.
	 *
	 * @return self
	 */
	public function setLabel( $label ) {
		$this->label = $label;

		return $this;
	}

	/**
	 * Get the value of public.
	 */
	public function isPublic() {
		return $this->public;
	}

	/**
	 * Set the value of public.
	 *
	 * @return self
	 */
	public function setPublic( $public ) {
		$this->public = $public;

		return $this;
	}

	/**
	 * Get the value of showInAdminStatusList.
	 */
	public function getShowInAdminStatusList() {
		return $this->showInAdminStatusList;
	}

	/**
	 * Set the value of showInAdminStatusList.
	 *
	 * @return self
	 */
	public function setShowInAdminStatusList( $showInAdminStatusList ) {
		$this->showInAdminStatusList = $showInAdminStatusList;

		return $this;
	}

	/**
	 * Get the value of showInAdminAllList.
	 */
	public function getShowInAdminAllList() {
		return $this->showInAdminAllList;
	}

	/**
	 * Set the value of showInAdminAllList.
	 *
	 * @return self
	 */
	public function setShowInAdminAllList( $showInAdminAllList ) {
		$this->showInAdminAllList = $showInAdminAllList;

		return $this;
	}

	/**
	 * Get the value of excludeFromSearch.
	 */
	public function isExcludedFromSearch() {
		return $this->excludeFromSearch;
	}

	/**
	 * Set the value of excludeFromSearch.
	 *
	 * @return self
	 */
	public function setExcludeFromSearch( $excludeFromSearch ) {
		$this->excludeFromSearch = $excludeFromSearch;

		return $this;
	}

	/**
	 * Get the value of labelCount.
	 */
	public function getLabelCount() {
		return $this->labelCount;
	}

	/**
	 * Set the value of labelCount.
	 *
	 * @return self
	 */
	public function setLabelCount( $labelCount ) {
		$this->labelCount = $labelCount;

		return $this;
	}

	public function registerStatus() {
		$args = [
			'label'                     => $this->getLabel(),
			'public'                    => $this->isPublic(),
			'exclude_from_search'       => $this->isExcludedFromSearch(),
			'show_in_admin_all_list'    => $this->getShowInAdminAllList(),
			'show_in_admin_status_list' => $this->getShowInAdminStatusList(),
			'label_count'               => $this->getLabelCount(),
		];
		register_post_status( $this->getStatusName(), $args );
	}

	/**
	 * @param $orderStatuses
	 * @return mixed
	 */
	public function addStatusToList( $orderStatuses ) {
		$orderStatuses[$this->getStatusName()] = $this->getLabel();
		return $orderStatuses;
	}

	public function save() {
		add_filter( 'init', [$this, 'registerStatus'] );
		add_filter( 'wc_order_statuses', [$this, 'addStatusToList'] );
	}
}
