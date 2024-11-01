(function ($) {
	const postApi = (url, body) =>
		fetch(url, {
			method: "POST",
			body: JSON.stringify(body),
			headers: {
				"Content-Type": "application/json",
				"X-WP-Nonce": uniqueCouponsPopup.api.nonce,
			},
		});

	const fetchCoupon = (groupId) =>
		postApi(uniqueCouponsPopup.api.retrieveCoupon, { group_id: groupId });

	const getCoupon = async (groupId) => {
		const response = await fetchCoupon(groupId);
		if (!response.ok) throw new Error("Response is not ok");
		const { value, expires_at: expiresAt } = await response.json();
		return { value, expiresAt };
	};

	const previewGetCoupon = async (_groupId) => {
		const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

		await sleep(1000);
		return {
			value: "Coupon-123",
			expiresAt: Date.now() / 1000,
		};
	};

	const addPopupEventHandlers = (popupElement, groupId) => {
		$(popupElement).on($.modal.OPEN, () => {
			postApi(uniqueCouponsPopup.api.onPopupOpen, { group_id: groupId });
		});
		$(popupElement).on($.modal.CLOSE, () => {
			postApi(uniqueCouponsPopup.api.onPopupClose, { group_id: groupId });
		});
	};

	window.addEventListener("DOMContentLoaded", (_event) => {
		const popupElement = document.querySelector(".unique-coupons-popup");
		if (!popupElement) return;

		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get("unique-coupons-preview")) {
			new Popup(popupElement, previewGetCoupon);
		} else {
			new Popup(
				popupElement,
				getCoupon,
				addPopupEventHandlers,
				uniqueCouponsPopup.timeoutInSeconds
			);
		}
	});

	class Popup {
		elements = {};
		groupId;
		canFetch;
		isFetching;
		getCoupon;
		addAdditionalEventListeners;
		timeoutInSeconds;

		constructor(
			element,
			getCoupon,
			addAdditionalEventListeners = (_container, _groupId) => {},
			timeoutInSeconds = 0
		) {
			this.elements.container = element;
			this.getCoupon = getCoupon;
			this.addAdditionalEventListeners = addAdditionalEventListeners;
			this.timeoutInSeconds = timeoutInSeconds;
			this.init();
		}

		init = () => {
			this.groupId = this.elements.container.dataset.groupId;
			if (!this.groupId) return;

			this.selectDomElements();
			this.initElements();
			this.setCanFetch(true);
			this.setIsFetching(false);
			this.addEventListeners();
			this.queuePopup();
		};

		selectDomElements = () => {
			[
				{ name: "button", selector: ".unique-coupons-popup__button" },
				{ name: "coupon", selector: ".unique-coupons-popup__coupon" },
				{ name: "value", selector: ".unique-coupons-popup__value" },
				{ name: "expiresAt", selector: ".unique-coupons-popup__expires-at" },
			].forEach(({ name, selector }) => {
				this.elements[name] = this.elements.container.querySelector(selector);
			});
			this.elements.spinner = $.parseHTML(
				`<div class="unique-coupons-popup__spinner">
					<div class="bounce1"></div>
					<div class="bounce2"></div>
					<div class="bounce3"></div>
				</div>`
			)[0];
		};

		initElements = () => {
			if (this.elements.button) {
				this.elements.button.appendChild(this.elements.spinner);
			}
			if (this.elements.coupon) this.elements.coupon.style.display = "none";
		};

		addEventListeners = () => {
			if (this.elements.button) {
				this.elements.button.addEventListener("click", () =>
					this.handleRetrieval()
				);
			}
			this.addAdditionalEventListeners(this.elements.container, this.groupId);
		};

		handleRetrieval = async () => {
			if (!this.canFetch) return;
			this.setCanFetch(false);
			this.setIsFetching(true);

			try {
				const coupon = await this.getCoupon(this.groupId);
				this.showCoupon(coupon);
			} catch (error) {
				this.handleResponseError();
			} finally {
				this.setIsFetching(false);
			}
		};

		showCoupon = ({ value, expiresAt }) => {
			if (this.elements.coupon) this.elements.coupon.style.display = "block";
			if (this.elements.value) this.elements.value.textContent = value;
			if (this.elements.expiresAt) {
				this.elements.expiresAt.textContent = this.getDateText(expiresAt);
				this.elements.expiresAt.style.whiteSpace = "nowrap";
			}
		};

		getDateText(timestamp) {
			const date = new Date();
			date.setTime(timestamp * 1000);
			return date.toLocaleDateString(undefined, {
				day: "numeric",
				month: "long",
				year: "numeric",
			});
		}

		handleResponseError = () => {
			this.setCanFetch(true);
		};

		queuePopup = () => {
			setTimeout(() => {
				$(this.elements.container).modal({
					fadeDuration: 300,
					fadeDelay: 0,
					blockerClass: "unique-coupons-jquery-modal",
				});
			}, this.timeoutInSeconds * 1000);
		};

		setCanFetch(canFetch) {
			this.canFetch = canFetch;
			if (this.elements.button) {
				this.elements.button.disabled = !canFetch;
				this.elements.button.style.pointerEvents = canFetch ? "" : "none";
				this.elements.button.style.cursor = canFetch ? "pointer" : "default";
			}
		}

		setIsFetching(isFetching) {
			this.isFetching = isFetching;
			this.elements.spinner.style.opacity = isFetching ? 1 : 0;
		}
	}
})(jQuery);
