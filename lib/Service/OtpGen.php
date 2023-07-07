<?php

/**
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\TwoFactor_Totp\Service;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use OCP\Defaults;
use OCP\IUser;

class OtpGen {
	/** @var Defaults */
	private $defaults;

	public function __construct(Defaults $defaults) {
		$this->defaults = $defaults;
	}

	/**
	 * Return an otpauth URI, for example "otpauth://totp/mySecretName?secret=ABCDEF&issuer=myself"
	 * @param IUser $user
	 * @param string $secret
	 * @return string the otpauth URI
	 */
	public function generateOtpUrl(IUser $user, string $secret) {
		$productName = $this->defaults->getName();
		$userName = $user->getCloudId();

		$secretName = \rawurlencode("$productName:$userName");
		$issuer = \rawurlencode($productName);

		return "otpauth://totp/$secretName?secret=$secret&issuer=$issuer";
	}

	/**
	 * Return a base64-encoded PNG image of the QR code containing the generated otpauth URI.
	 * The returned string will always contain the "data:image/png;base64," prefix, which is
	 * suitable to use in an "img" HTML tag
	 * ```
	 * $qr = $otpGen->genOtpQR($user, $secret);
	 * $img = "<img src=\"$qr\" />"
	 * ```
	 * @param IUser $user
	 * @param string $secret
	 * @return string a base64-encoded PNG image of the QR, with the "data:image/png;base64," prefix
	 */
	public function generateOtpQR(IUser $user, string $secret) {
		$data = $this->generateOtpUrl($user, $secret);
		$renderer = new ImageRenderer(
			new RendererStyle(170),
			new ImagickImageBackEnd()
		);
		$writer = new Writer($renderer);
		return 'data:image/png;base64,' . \base64_encode($writer->writeString($data));
	}
}
