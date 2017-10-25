<?php
defined( 'ABSPATH' ) or exit;
?>
<style type="text/css">

	* {
		box-sizing: border-box;
	}

	html, body {
		background: #FFFFFF;
		width: 100%;
		height: 100%;
		margin: 0;
		padding: 0;
	}

	body {
		display: block;
		color: #000000;
		padding: 15px;
		-webkit-print-color-adjust: exact;
	}

	h1 {
		font-weight: 400;
	}

	a {
		color: <?php echo get_option( 'wc_pip_link_color', '#000000' ); ?>;
	}

	.container {
		min-width: 700px;
		width: 960px;
		max-width: 90%;
		display: block;
		margin: auto;
		overflow: hidden;
	}

	.main-header,
	.sub-section,
	.section-body,
	.row {
		display: flex;
		flex-direction: row;
		flex-wrap: nowrap;
		justify-content: space-between;
		align-items: center;
	}

	.row {
		width: 100%;
		margin-bottom: 10px;
	}

	.main-header {
		max-width: 100%;
		padding: 0 15px;
		background: #ffcc00;
	}

	header .logo {
		max-width: 200px;
	}

	header .header-barcode {
		max-width: 25%;
	}

	header .barcode {
		max-width: 100%;
	}

	.section-header {
		background: #ffcc00;
		padding: 10px 15px;
	}

	.section-header span.num {
		display: inline-block;
		background: #d40411;
		color: #fff;
		width: 1.5em;
		height: 1.5em;
		padding: 2px;
		margin-right: 5px;
		font-size: 14px;
		text-align: center;
		vertical-align: middle;
	}

	.section-header:not(.num) {
		font-weight: 600;
	}

	.section-body {
		padding: 25px 15px;

	}

	.section-body img {
		max-width: 400px;
	}

	.name {
		display: inline-block;
		white-space: nowrap;
		margin-right: 10px;
	}

	.box {
		display: inline-block;
		border: 2px solid #000;
		min-height: 2.5em;
		width: 100%;
		min-width: 50px;
		padding: 5px 10px;
		font-size: 1.1em;
		line-height: 1.4em;
	}

	.section-1 > * {
		width: 40%;
	}

	img.img-acc-no {
		max-width: 100%;
	}

	.section-2 {
		align-items: flex-start;
	}

	.section-2 .box {
		width: 100%;
	}

	.row-2 {
		align-items: flex-end;
	}

	.row-2 > *:not(.dist-item) {
		margin-bottom: 15px;
	}

	.handover-option {
		margin-right: 15px;
	}

	.handover-option,
	.handover-option .circle {
		display: inline-block;
	}

	.handover-option .circle {
		width: 1.1em;
		height: 1.1em;
		border: 1px solid #000;
		border-radius: 50%;
		position: relative;
	}

	.handover-option .circle.active:after {
		content: '';
		position: absolute;
		background: #000;
		width: .6em;
		height: .6em;
		border-radius: 50%;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	.underline-box {
		flex: 1 0 auto;
		border-bottom: 2px solid #000;
		min-width: 75%;
		min-height: 2em;
	}

	.row-title {
		padding-right: 15px;
	}

	.item {
		text-align: center;
		width: 24.5%;
	}

	.dist-item {
		text-align: center;
		width: 53%;
	}

	.dist-item .box {
		text-align: left;
	}

	.section-3 {
		flex-direction: column;
	}

	.section-4 {
		display: inline-block;
	}

	.section-4 .sub-section {
		width: 100%;
	}

	.section-4 .sub-section > *:first-child {
		padding-right: 30px;
	}

	.section-4 .sub-section > *:nth-child(2) {
		margin-right: auto;
		border-bottom: 2px solid #000;
		padding: 10px;
		width: 40%;
	}

	.print-button {
		margin: 50px auto 65px auto;
		display: block;
		text-decoration: none;
		text-align: center;
		font-size: 25px;
	}

	@media print {
		.print-button {
			display: none;
		}
	}

</style>