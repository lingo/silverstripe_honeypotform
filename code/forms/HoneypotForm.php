<?php

/**
 * Honeypot form, auto-generates a honeypot field with random name
 * and ensures it's correctly empty on submission.
 * This check is provided via a separate function so that it doesn't
 * interrupt the validation system, which is designed to show a message
 * to the user; exactly what we don't want in this case.
 */
class HoneypotForm extends Form {

	/**
	 * The minimum amount of time (seconds) to fill in the form.
	 * We assume that a quicker formfill than this is a bot.
	 * @var integer
	 */
	public static $minimum_formfill_seconds = 10;

	/**
	 * If this is false, then timestamp fields won't be added or checked
	 * @var boolean
	 */
	public static $use_timestamps = false;

	/**
	 * The hash used as a token f0r this form.
	 * @var string
	 */
	protected $honeypot         = '';

	/**
	 * Randomized value used as a css classname to
	 * keep the honeypot element hidden from users.
	 * @var string
	 */
	protected static $css_class = '';

	/**
	 * Fetch this token from the session
	 * @return string Honeypot hash
	 */
	protected function getToken() {
		$this->honeypot = Session::get('HoneypotForm.' . $this->Name . '.Honeypot');
		return $this->honeypot;
	}

	public function getHoneypotFieldName() {
		$this->getToken();
		if (!$this->honeypot) {
			$this->setToken();
		}
		return $this->getToken();
	}

	/**
	 * Set the token (more correctly generate).
	 * @uses Session
	 */
	public function setToken($token=null) {
		if (!$token) {
			$generator = new RandomGenerator();
			$token     = 'hp_' . $generator->randomToken('sha1');
		}
		$this->honeypot = $token;
		Session::set('HoneypotForm.' . $this->Name . '.Honeypot', $this->honeypot);
		return $this->honeypot;
	}


	/**
	 * Generate the name of the timestamp field
	 * @return string
	 */
	protected function getTimeFieldName() {
		return md5($this->honeypot . 'timestamp');
	}

	/**
	 * Create a new Honeypot form, that is a form with a honeypot field; if the
	 * honeypot field is filled in, the form submission will silently fail.
	 * This is designed to catch spambots/etc, as users should not fill it in (both
	 * labeled as such, and hidden via CSS).
	 *
	 * @param Controller $controller 	@see Form::__construct
	 * @param string $name       		@see Form::__construct
	 * @param FieldSet $fields     	@see Form::__construct
	 * @param FieldSet $actions    	@see Form::__construct
	 * @param Validator $validator  	@see Form::__construct
	 */
	public function __construct($controller, $name, FieldList $fields=null, FieldList $actions=null, $validator=null) {
		$this->Name = $name;
		if (!$this->getToken()) {
			$this->setToken();
		}
		$field = new TextField($this->honeypot, 'Please do not fill in this field');
		$field->addExtraClass(self::$css_class);
		$fields->push($field);

		if (self::$use_timestamps) {
			$timeField = new HiddenField($this->getTimeFieldName());
			$timeField->setValue(time());
			$fields->push($timeField);
		}
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	/**
	 * Check whether the honeypot field was (thus incorrectly) filled in.
	 * @param  array $data Form submit data
	 * @return boolean       true if field was left empty as desired.
	 */
	public function validateHoneypot($data) {
		$fieldName      = $this->getToken();
		$timestampField = $this->getTimeFieldName();
		$now            = time();
		$then           = isset($data[$timestampField]) ? $data[$timestampField] : $now;
		$notTooFast     = (($now - $then) > self::$minimum_formfill_seconds);

		if (!self::$use_timestamps) {
			$notTooFast = true;
		}

		if (isset($data[$fieldName])
			&& empty($data[$fieldName])
			&& $notTooFast) {
			return true;
		}
		// If we get here, then the invisible Honeypot field has been filled in, lets assume by a bot.
		// Log it and drop it silently.
		SS_Log::log(new Exception('Possible bot attack in ' . $this->Name . ' from IP ' . $_SERVER['REMOTE_ADDR']), SS_Log::WARN);
		return false;
	}

	/**
	 * Render the CSS for the honeypot field so that the field is hidden, without
	 * using a predictable classname or an inline 'display:none'.
	 * Doesn't stop a clever bot with javascript!
	 *
	 * @uses  Requirements::customCSS to render the css to the page
	 */
	public static function render_css() {
		$cssClass = Session::get('HoneypotForm.CSSClass');
		if (!$cssClass) {
			$generator = new RandomGenerator();
			$cssClass  = 'hp_' . $generator->randomToken('sha1');
			Session::set('HoneypotForm.CSSClass', $cssClass);
		}
		self::$css_class = $cssClass;
		Requirements::customCSS(<<<CSS
			.{$cssClass} {
				display: none;
			}
CSS
		);
	}
}