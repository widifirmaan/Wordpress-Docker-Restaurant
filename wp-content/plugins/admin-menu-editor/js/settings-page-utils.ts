jQuery(function (_alias) {
	if (typeof _alias === 'undefined') {
		//Should never happen, but is necessary because the TS definition for jQuery() says
		//the argument is optional (implies it can be undefined).
		return;
	}
	const $: JQueryStatic = _alias;
	const $window = $(window);

	//region Tab utilities
	const menuEditorHeading = $('#ws_ame_editor_heading').first();
	const pageWrapper = menuEditorHeading.closest('.wrap');
	const tabList = pageWrapper.find('.nav-tab-wrapper').first();

	//On AME pages, move settings tabs after the heading. This is necessary to make them appear on the right side,
	//and WordPress breaks that by moving notices like "Settings saved" after the first H1 (see common.js).
	const menuEditorTabs = tabList.add(tabList.next('.clear'));
	if ((menuEditorHeading.length > 0) && (menuEditorTabs.length > 0)) {
		menuEditorTabs.insertAfter(menuEditorHeading);
	}

	//Add size classes to each tab to enable fixed-increment resizing.
	const tabColumnWidth = parseInt((tabList.css('--ame-tab-col-width') || '32px').replace('px', ''), 10);
	const tabCondensedHorizontalPadding = parseInt((tabList.css('--ame-tab-cnd-horizontal-padding') || '8px').replace('px', ''), 10);
	const tabCondensedGap = parseInt((tabList.css('--ame-tab-cnd-gap') || '5px').replace('px', ''), 10);

	function calculateColumns(contentWidth: number, condensedPadding?: number) {
		if (typeof condensedPadding === 'undefined') {
			condensedPadding = tabCondensedHorizontalPadding;
		}
		//Calculate the lowest number of columns that would fit a tab with the given content width.
		//Minimum width = content width + padding for condensed tabs + border.
		const condensedWidth = contentWidth + condensedPadding * 2 + 2;
		return Math.min(Math.ceil(
			(condensedWidth + tabCondensedGap) / (tabColumnWidth + tabCondensedGap)
		), 12);
	}

	tabList.children('.nav-tab').each(function (_, element) {
		const $this = $(element);
		const columnCount = calculateColumns($this.width());
		$this.addClass('ame-nav-tab-col-' + columnCount);
	});

	//Also add a size class to the heading that's inside the tab list. That heading starts hidden,
	//so we use the size of the main heading to determine the column count.
	const $inlineHeading = tabList.find('#ws_ame_tab_leader_heading');
	if (($inlineHeading.length > 0) && (menuEditorHeading.length > 0)) {
		const headingColumnCount = calculateColumns(menuEditorHeading.width(), 0);
		$inlineHeading.addClass('ame-nav-tab-col-' + headingColumnCount);
	}

	//Switch tab styles when there are too many tabs and they don't fit on one row.
	let $firstTab: JQuery | null = null,
		$lastTab: JQuery | null = null,
		knownTabWrapThreshold = -1;

	function updateTabStyles() {
		if (($firstTab === null) || ($lastTab === null)) {
			const $tabItems = tabList.children('.nav-tab');
			$firstTab = $tabItems.first();
			$lastTab = $tabItems.last();
		}

		//To detect if any tabs are wrapped to the next row, check if the top of the last tab
		//is below the bottom of the first tab.
		const firstPosition = $firstTab.position();
		const lastPosition = $lastTab.position();
		const windowWidth = $(window).width();
		//Sanity check.
		if (
			!firstPosition || !lastPosition || !windowWidth
			|| (typeof firstPosition['top'] === 'undefined')
			|| (typeof lastPosition['top'] === 'undefined')
		) {
			return;
		}
		const firstTabBottom = firstPosition.top + $firstTab.outerHeight();
		//Note: The -1 below is due to the active tab having a negative bottom margin.
		const areTabsWrapped = (lastPosition.top >= (firstTabBottom - 1));

		//Tab positions may change when we apply different styles, which could lead to the tab bar
		//rapidly cycling between one and two rows when the browser width is just right.
		//To prevent that, remember what the width was when we detected wrapping, and always apply
		//the alternative styles if the width is lower than that.
		const wouldWrapByDefault = (windowWidth <= knownTabWrapThreshold);

		const tooManyTabs = areTabsWrapped || wouldWrapByDefault;
		if (tooManyTabs && (windowWidth > knownTabWrapThreshold)) {
			knownTabWrapThreshold = windowWidth;
		}

		pageWrapper.toggleClass('ws-ame-too-many-tabs', tooManyTabs);
	}

	updateTabStyles();

	menuEditorHeading.css('visibility', 'visible');
	tabList.css('visibility', 'visible');

	if (tabList.length > 0) {
		$window.on('resize', wsAmeLodash.debounce(
			function () {
				updateTabStyles();
			},
			300
		));
	}
	//endregion

	//region Sticky top bar
	const stickyBar = $('.ame-sticky-top-bar');
	if (stickyBar.length > 0) {
		const $wpContent = stickyBar.closest('#wpcontent').first();

		//Add a "pinned" class to the bar when it becomes sticky.
		if (IntersectionObserver) {
			const $adminBar = $('#wpadminbar').first();
			let lastAdminBarHeight: number = $adminBar.outerHeight() || 0;
			let currentObserver: IntersectionObserver | null = null;

			function restartObserver(adminBarHeight: number) {
				if (currentObserver) {
					currentObserver.disconnect();
					currentObserver = null;
				}

				//The bar sticks to the bottom of the admin bar.
				let observerRootMargin = '-33px';
				if (adminBarHeight > 0) {
					observerRootMargin = (-1 * adminBarHeight - 1) + 'px';
				}
				lastAdminBarHeight = adminBarHeight;

				const observer = new IntersectionObserver(
					(entries) => {
						let lastPinnedBar: null | Element = null;
						for (const e of entries) {
							const isPinned = e.intersectionRatio < 1;
							e.target.classList.toggle('ame-is-pinned-top-bar', isPinned);
							if (isPinned) {
								lastPinnedBar = e.target;
							}
						}

						//Store the height of the bar in a CSS variable. This is useful if a module
						//wants to stack other sticky elements below the bar.
						//(We assume there will only be one bar in practice.)
						if (lastPinnedBar) {
							$wpContent.css(
								'--ame-sticky-bar-last-pinned-height',
								Math.round($(lastPinnedBar).outerHeight()) + 'px'
							);
						}
					},
					{
						threshold: [1],
						rootMargin: observerRootMargin + ' 0px 0px 0px'
					}
				);

				stickyBar.each((_, element) => {
					observer.observe(element);
				});

				currentObserver = observer;

				//Calculate how far the sides of the bar are from the sides of the page container.
				//This is useful if we want the bar to fill the page when in its pinned state.
				const wpContentRect = $wpContent.get(0)?.getBoundingClientRect();

				stickyBar.each((_, element) => {
					const $bar = $(element);
					let barLeftOffset = 0;
					let barRightOffset = 0;

					//Basically, we look at the distance between the bar's parent element and
					//the #wpcontent container. Then we add the parent's padding to that difference.
					//This should effectively tell us how far the bar needs to expand to fill
					//the container horizontally.
					const parent = $bar.parent().not('#wpcontent').get(0);
					const parentRect = parent?.getBoundingClientRect();
					if (parentRect && wpContentRect) {
						barLeftOffset = Math.floor(parentRect.left - wpContentRect.left);
						barRightOffset = Math.floor(wpContentRect.right - parentRect.right);
					}

					let parentStyle: CSSStyleDeclaration | null;
					try {
						parentStyle = window.getComputedStyle(parent); //Throws if parent is invalid.
					} catch (e) {
						parentStyle = null;
					}
					if (parentStyle) {
						const parentPaddingLeft = parseInt(
							parentStyle.getPropertyValue('padding-left'),
							10
						);
						const parentPaddingRight = parseInt(
							parentStyle.getPropertyValue('padding-right'), 10
						);

						if (!isNaN(parentPaddingLeft)) {
							barLeftOffset += parentPaddingLeft;
						}
						if (!isNaN(parentPaddingRight)) {
							barRightOffset += parentPaddingRight;
						}
					}

					$bar.css({
						'--ame-sticky-bar-left-offset': barLeftOffset + 'px',
						'--ame-sticky-bar-right-offset': barRightOffset + 'px'
					});
				});
			}

			restartObserver(lastAdminBarHeight);

			$window.on('resize', wsAmeLodash.debounce(
				function () {
					const adminBarHeight = $adminBar.outerHeight() || 0;
					if ((adminBarHeight !== lastAdminBarHeight)) {
						restartObserver(adminBarHeight);
					}
				},
				800,
				{leading: true}
			));
		}
	}
	//endregion
});

