<?php
/**
 * Accessibility rule checks against a parsed DOMDocument.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * Static rule runner: evaluates a DOMDocument and returns all found Issues.
 */
class Rules {

	/**
	 * Run all rules against the parsed DOM and return found issues.
	 *
	 * @param \DOMDocument $dom   Parsed document.
	 * @param \DOMXPath    $xpath XPath evaluator for the document.
	 * @param string       $url   Source URL (for the Issue url field).
	 * @return Issue[]
	 */
	public static function run( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$issues = array();

		$checks = array(
			'check_img_alt_missing',
			'check_html_lang_missing',
			'check_page_title_missing',
			'check_input_label_missing',
			'check_image_input_no_alt',
			'check_link_no_text',
			'check_button_no_name',
			'check_heading_structure',
			'check_skiplink_missing',
			'check_main_landmark_missing',
			'check_duplicate_ids',
			'check_link_new_window',
		);

		foreach ( $checks as $method ) {
			$issue = self::$method( $dom, $xpath, $url );
			if ( null !== $issue ) {
				$issues[] = $issue;
			}
		}

		return $issues;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Truncate a string to the given length, appending ellipsis if needed.
	 *
	 * @param string $str    Input string.
	 * @param int    $length Max length.
	 * @return string
	 */
	private static function truncate( $str, $length = 120 ) {
		$str = trim( $str );
		if ( mb_strlen( $str ) <= $length ) {
			return $str;
		}
		return mb_substr( $str, 0, $length ) . '…';
	}

	/**
	 * Return the outer HTML of a DOMElement, truncated.
	 *
	 * @param \DOMElement $el     Element to serialize.
	 * @param int         $length Max length.
	 * @return string
	 */
	private static function outer_html( \DOMElement $el, $length = 120 ) {
		$doc = $el->ownerDocument;
		if ( ! $doc ) {
			return '';
		}
		return self::truncate( $doc->saveHTML( $el ), $length );
	}

	/**
	 * Return the trimmed text content of an element, checking descendant text nodes.
	 *
	 * @param \DOMElement $el Element.
	 * @return string
	 */
	private static function visible_text( \DOMElement $el ) {
		return trim( $el->textContent );
	}

	// -------------------------------------------------------------------------
	// Rule: img_alt_missing (WCAG 1.1.1, serious)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_img_alt_missing( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		// Images that have NO alt attribute at all (alt="" is decorative and valid).
		$nodes = $xpath->query( '//img[not(@alt)]' );
		if ( ! $nodes || 0 === $nodes->length ) {
			return null;
		}
		$context = '';
		if ( $nodes->item( 0 ) instanceof \DOMElement ) {
			$context = self::outer_html( $nodes->item( 0 ) );
		}
		return new Issue(
			array(
				'rule_id'     => 'img_alt_missing',
				'severity'    => 'serious',
				'wcag'        => '1.1.1',
				'title'       => __( 'Bild ohne Alt-Attribut', 'barrierefrei-check' ),
				'description' => __( 'Ein oder mehrere <img>-Elemente besitzen kein alt-Attribut. Screenreader können den Inhalt dieser Bilder nicht vermitteln.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge jedem informativen Bild ein aussagekräftiges alt-Attribut hinzu, z. B. alt="Diagramm zeigt Umsatzwachstum Q1 2024". Rein dekorative Bilder erhalten alt="".', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => $nodes->length,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: html_lang_missing (WCAG 3.1.1, critical)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_html_lang_missing( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$nodes = $xpath->query( '//html[not(@lang) or @lang=""]' );
		if ( ! $nodes || 0 === $nodes->length ) {
			return null;
		}
		$context = '';
		$item    = $nodes->item( 0 );
		if ( $item instanceof \DOMElement ) {
			// Nur das öffnende Tag, nicht das gesamte Dokument.
			$context = '<html';
			if ( $item->hasAttributes() ) {
				foreach ( $item->attributes as $attr ) {
					$context .= ' ' . $attr->name . '="' . $attr->value . '"';
				}
			}
			$context .= '>';
			$context  = self::truncate( $context );
		}
		return new Issue(
			array(
				'rule_id'     => 'html_lang_missing',
				'severity'    => 'critical',
				'wcag'        => '3.1.1',
				'title'       => __( 'Seitensprache fehlt', 'barrierefrei-check' ),
				'description' => __( 'Das <html>-Element hat kein lang-Attribut oder es ist leer. Screenreader wählen dann eine falsche Aussprache.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Setze das lang-Attribut am <html>-Tag, z. B. <html lang="de"> für Deutsch oder <html lang="de-DE"> für Deutschland-spezifisches Deutsch.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => 1,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: page_title_missing (WCAG 2.4.2, serious)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_page_title_missing( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$nodes = $xpath->query( '//title' );
		if ( $nodes && $nodes->length > 0 ) {
			$title_node = $nodes->item( 0 );
			if ( $title_node && '' !== trim( $title_node->textContent ) ) {
				return null;
			}
		}
		return new Issue(
			array(
				'rule_id'     => 'page_title_missing',
				'severity'    => 'serious',
				'wcag'        => '2.4.2',
				'title'       => __( 'Seitentitel fehlt oder ist leer', 'barrierefrei-check' ),
				'description' => __( 'Die Seite hat kein <title>-Element oder der Titel ist leer. Screenreader-Nutzer und Suchmaschinen können die Seite dann nicht einordnen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge einen aussagekräftigen <title> im <head>-Bereich hinzu, der den Inhalt der Seite und den Seitennamen enthält, z. B. "Kontakt – Meine Website".', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => '<title></title>',
				'count'       => 1,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: input_label_missing (WCAG 1.3.1, serious)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_input_label_missing( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		// Collect all labelable controls excluding hidden/submit/button/image.
		$control_nodes = $xpath->query(
			'//input[not(@type) or (@type!="hidden" and @type!="submit" and @type!="button" and @type!="image")] | //select | //textarea'
		);
		if ( ! $control_nodes ) {
			return null;
		}

		// Build map of all label[for] values for quick lookup.
		$label_for_ids = array();
		$label_nodes   = $xpath->query( '//label[@for]' );
		if ( $label_nodes ) {
			foreach ( $label_nodes as $lbl ) {
				if ( $lbl instanceof \DOMElement ) {
					$label_for_ids[ $lbl->getAttribute( 'for' ) ] = true;
				}
			}
		}

		$count   = 0;
		$context = '';

		foreach ( $control_nodes as $ctrl ) {
			if ( ! ( $ctrl instanceof \DOMElement ) ) {
				continue;
			}

			// Check aria-label.
			$aria_label = trim( $ctrl->getAttribute( 'aria-label' ) );
			if ( '' !== $aria_label ) {
				continue;
			}

			// Check aria-labelledby.
			$aria_lb = trim( $ctrl->getAttribute( 'aria-labelledby' ) );
			if ( '' !== $aria_lb ) {
				continue;
			}

			// Check label[for=id].
			$id = trim( $ctrl->getAttribute( 'id' ) );
			if ( '' !== $id && isset( $label_for_ids[ $id ] ) ) {
				continue;
			}

			// Check if wrapped in a <label>.
			$parent = $ctrl->parentNode;
			$in_label = false;
			while ( $parent instanceof \DOMElement ) {
				if ( 'label' === strtolower( $parent->tagName ) ) {
					$in_label = true;
					break;
				}
				$parent = $parent->parentNode;
			}
			if ( $in_label ) {
				continue;
			}

			$count++;
			if ( '' === $context ) {
				$context = self::outer_html( $ctrl );
			}
		}

		if ( 0 === $count ) {
			return null;
		}

		return new Issue(
			array(
				'rule_id'     => 'input_label_missing',
				'severity'    => 'serious',
				'wcag'        => '1.3.1',
				'title'       => __( 'Formularelement ohne Label', 'barrierefrei-check' ),
				'description' => __( 'Ein oder mehrere Formularelemente (input, select, textarea) haben kein zugeordnetes Label. Screenreader können den Zweck des Feldes nicht ansagen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Verknüpfe jedes Formularelement mit einem <label for="id">-Element oder verwende aria-label / aria-labelledby. Alternativ das Eingabefeld direkt in ein <label>-Element einbetten.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => $count,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: image_input_no_alt (WCAG 1.1.1, serious)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_image_input_no_alt( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$nodes = $xpath->query( '//input[@type="image" and (not(@alt) or @alt="")]' );
		if ( ! $nodes || 0 === $nodes->length ) {
			return null;
		}
		$context = '';
		if ( $nodes->item( 0 ) instanceof \DOMElement ) {
			$context = self::outer_html( $nodes->item( 0 ) );
		}
		return new Issue(
			array(
				'rule_id'     => 'image_input_no_alt',
				'severity'    => 'serious',
				'wcag'        => '1.1.1',
				'title'       => __( 'Bild-Schaltfläche ohne Alt-Text', 'barrierefrei-check' ),
				'description' => __( 'Ein <input type="image"> hat kein oder leeres alt-Attribut. Diese Schaltflächen müssen eine Textalternative haben, da sie eine Funktion ausführen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge dem <input type="image"> ein beschreibendes alt-Attribut hinzu, das die Funktion der Schaltfläche beschreibt, z. B. alt="Formular absenden".', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => $nodes->length,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: link_no_text (WCAG 2.4.4, serious)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_link_no_text( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$nodes = $xpath->query( '//a[@href]' );
		if ( ! $nodes ) {
			return null;
		}

		$count   = 0;
		$context = '';

		foreach ( $nodes as $link ) {
			if ( ! ( $link instanceof \DOMElement ) ) {
				continue;
			}

			// Visible text.
			if ( '' !== self::visible_text( $link ) ) {
				continue;
			}

			// aria-label.
			if ( '' !== trim( $link->getAttribute( 'aria-label' ) ) ) {
				continue;
			}

			// title attribute.
			if ( '' !== trim( $link->getAttribute( 'title' ) ) ) {
				continue;
			}

			// Descendant img with non-empty alt.
			$imgs = $xpath->query( './/img[@alt and @alt!=""]', $link );
			if ( $imgs && $imgs->length > 0 ) {
				continue;
			}

			$count++;
			if ( '' === $context ) {
				$context = self::outer_html( $link );
			}
		}

		if ( 0 === $count ) {
			return null;
		}

		return new Issue(
			array(
				'rule_id'     => 'link_no_text',
				'severity'    => 'serious',
				'wcag'        => '2.4.4',
				'title'       => __( 'Link ohne erkennbaren Text', 'barrierefrei-check' ),
				'description' => __( 'Ein oder mehrere Links (<a href>) haben weder sichtbaren Text noch einen aria-label, title oder ein Bild mit Alt-Text. Tastatur- und Screenreader-Nutzer können den Linkzweck nicht erkennen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge sichtbaren Linktext oder ein aria-label hinzu, das den Linkzweck beschreibt. Bei Icon-Links: Icon-Bild mit alt-Attribut oder ein visuell verstecktes <span> mit Text versehen.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => $count,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: button_no_name (WCAG 4.1.2, serious)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_button_no_name( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$count   = 0;
		$context = '';

		// <button> elements with no visible text, no aria-label, no title.
		$buttons = $xpath->query( '//button' );
		if ( $buttons ) {
			foreach ( $buttons as $btn ) {
				if ( ! ( $btn instanceof \DOMElement ) ) {
					continue;
				}
				if ( '' !== self::visible_text( $btn ) ) {
					continue;
				}
				if ( '' !== trim( $btn->getAttribute( 'aria-label' ) ) ) {
					continue;
				}
				if ( '' !== trim( $btn->getAttribute( 'title' ) ) ) {
					continue;
				}
				$count++;
				if ( '' === $context ) {
					$context = self::outer_html( $btn );
				}
			}
		}

		// <input type="submit|button"> with empty value and no aria-label.
		$input_btns = $xpath->query( '//input[@type="submit" or @type="button"]' );
		if ( $input_btns ) {
			foreach ( $input_btns as $inp ) {
				if ( ! ( $inp instanceof \DOMElement ) ) {
					continue;
				}
				$val = trim( $inp->getAttribute( 'value' ) );
				if ( '' !== $val ) {
					continue;
				}
				if ( '' !== trim( $inp->getAttribute( 'aria-label' ) ) ) {
					continue;
				}
				$count++;
				if ( '' === $context ) {
					$context = self::outer_html( $inp );
				}
			}
		}

		if ( 0 === $count ) {
			return null;
		}

		return new Issue(
			array(
				'rule_id'     => 'button_no_name',
				'severity'    => 'serious',
				'wcag'        => '4.1.2',
				'title'       => __( 'Schaltfläche ohne zugänglichen Namen', 'barrierefrei-check' ),
				'description' => __( 'Eine oder mehrere Schaltflächen haben weder sichtbaren Text noch aria-label / title. Assistive Technologien können die Funktion der Schaltfläche nicht ansagen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge sichtbaren Text in das <button>-Element ein oder verwende aria-label. Bei Icon-Buttons: aria-label="Menü öffnen" oder ein visuell verstecktes <span> mit Text.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => $count,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: heading_structure (WCAG 1.3.1, moderate)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_heading_structure( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$headings = $xpath->query( '//h1|//h2|//h3|//h4|//h5|//h6' );
		if ( ! $headings || 0 === $headings->length ) {
			// Keine Überschriften überhaupt – eigene Regel wäre sinnvoll, aber hier kein Fund.
			return null;
		}

		$levels  = array();
		$context = '';
		foreach ( $headings as $h ) {
			if ( $h instanceof \DOMElement ) {
				$levels[] = (int) substr( $h->tagName, 1 );
			}
		}

		// Prüfe ob h1 vorhanden.
		if ( ! in_array( 1, $levels, true ) ) {
			return new Issue(
				array(
					'rule_id'     => 'heading_structure',
					'severity'    => 'moderate',
					'wcag'        => '1.3.1',
					'title'       => __( 'Kein H1 vorhanden', 'barrierefrei-check' ),
					'description' => __( 'Die Seite enthält keine <h1>-Überschrift. H1 ist die Hauptüberschrift und strukturell für assistive Technologien wichtig.', 'barrierefrei-check' ),
					'how_to_fix'  => __( 'Füge genau eine <h1>-Überschrift pro Seite hinzu, die den Hauptinhalt beschreibt. Untergeordnete Abschnitte nutzen <h2>, <h3> usw.', 'barrierefrei-check' ),
					'url'         => $url,
					'context'     => '',
					'count'       => 1,
				)
			);
		}

		// Prüfe ob Ebenen übersprungen werden.
		$skipped_context = '';
		for ( $i = 1; $i < count( $levels ); $i++ ) {
			$prev = $levels[ $i - 1 ];
			$curr = $levels[ $i ];
			if ( $curr > $prev + 1 ) {
				// Ebene übersprungen.
				$skipped_context = 'h' . $prev . ' → h' . $curr;
				break;
			}
		}

		if ( '' === $skipped_context ) {
			return null;
		}

		return new Issue(
			array(
				'rule_id'     => 'heading_structure',
				'severity'    => 'moderate',
				'wcag'        => '1.3.1',
				'title'       => __( 'Überschriftenebene übersprungen', 'barrierefrei-check' ),
				'description' => __( 'Die Überschriftenhierarchie springt eine Ebene (z. B. von H2 direkt zu H4). Das erschwert die Navigation per Screenreader.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Halte die Überschriftenhierarchie konsistent: Nach einer H2 kommt H3, nicht H4 oder H5. Überspringe keine Ebenen beim "Hinuntergehen" der Hierarchie.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $skipped_context,
				'count'       => 1,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: skiplink_missing (WCAG 2.4.1, moderate)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_skiplink_missing( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		// Heuristik: Prüfe die ersten 3 Links auf Fragment-Ziele (#id, nicht nur #).
		$links = $xpath->query( '//a[@href]' );
		if ( ! $links ) {
			return new Issue(
				array(
					'rule_id'     => 'skiplink_missing',
					'severity'    => 'moderate',
					'wcag'        => '2.4.1',
					'title'       => __( 'Skip-Link fehlt', 'barrierefrei-check' ),
					'description' => __( 'Die Seite scheint keinen Skip-Link ("Zum Hauptinhalt springen") zu enthalten. Tastaturnutzer müssen sonst bei jeder Seite alle Navigationspunkte durchlaufen.', 'barrierefrei-check' ),
					'how_to_fix'  => __( 'Füge am Seitenanfang einen Skip-Link ein: <a href="#main-content">Zum Hauptinhalt springen</a>. Das Ziel-Element bekommt id="main-content". Der Link kann visuell versteckt sein und nur beim Fokus erscheinen.', 'barrierefrei-check' ),
					'url'         => $url,
					'context'     => '',
					'count'       => 1,
				)
			);
		}

		$checked = 0;
		foreach ( $links as $link ) {
			if ( $checked >= 3 ) {
				break;
			}
			if ( ! ( $link instanceof \DOMElement ) ) {
				continue;
			}
			$href = trim( $link->getAttribute( 'href' ) );
			// Ein Fragment-Link, der nicht nur '#' ist.
			if ( '#' === substr( $href, 0, 1 ) && strlen( $href ) > 1 ) {
				return null;
			}
			$checked++;
		}

		return new Issue(
			array(
				'rule_id'     => 'skiplink_missing',
				'severity'    => 'moderate',
				'wcag'        => '2.4.1',
				'title'       => __( 'Skip-Link fehlt', 'barrierefrei-check' ),
				'description' => __( 'Die Seite scheint keinen Skip-Link ("Zum Hauptinhalt springen") zu enthalten. Tastaturnutzer müssen sonst bei jeder Seite alle Navigationspunkte durchlaufen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge am Seitenanfang einen Skip-Link ein: <a href="#main-content">Zum Hauptinhalt springen</a>. Das Ziel-Element bekommt id="main-content". Der Link kann visuell versteckt sein und nur beim Fokus erscheinen.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => '',
				'count'       => 1,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: main_landmark_missing (WCAG 1.3.1, moderate)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_main_landmark_missing( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$main    = $xpath->query( '//main' );
		$role    = $xpath->query( '//*[@role="main"]' );

		$has_main = ( $main && $main->length > 0 );
		$has_role = ( $role && $role->length > 0 );

		if ( $has_main || $has_role ) {
			return null;
		}

		return new Issue(
			array(
				'rule_id'     => 'main_landmark_missing',
				'severity'    => 'moderate',
				'wcag'        => '1.3.1',
				'title'       => __( 'Main-Landmark fehlt', 'barrierefrei-check' ),
				'description' => __( 'Die Seite hat kein <main>-Element und kein Element mit role="main". Screenreader-Nutzer können den Hauptinhalt nicht direkt anspringen.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Umschließe den Hauptinhalt der Seite mit einem <main>-Element oder füge role="main" zu einem geeigneten Container hinzu. Pro Seite sollte es genau ein <main>-Element geben.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => '',
				'count'       => 1,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: duplicate_ids (WCAG 4.1.1, minor)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_duplicate_ids( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$nodes = $xpath->query( '//*[@id]' );
		if ( ! $nodes ) {
			return null;
		}

		$seen      = array();
		$dupes     = array();

		foreach ( $nodes as $el ) {
			if ( ! ( $el instanceof \DOMElement ) ) {
				continue;
			}
			$id = $el->getAttribute( 'id' );
			if ( '' === $id ) {
				continue;
			}
			if ( isset( $seen[ $id ] ) ) {
				$dupes[ $id ] = true;
			} else {
				$seen[ $id ] = true;
			}
		}

		if ( empty( $dupes ) ) {
			return null;
		}

		$dupe_list = implode( ', ', array_keys( $dupes ) );

		return new Issue(
			array(
				'rule_id'     => 'duplicate_ids',
				'severity'    => 'minor',
				'wcag'        => '4.1.1',
				'title'       => __( 'Doppelte ID-Attribute', 'barrierefrei-check' ),
				'description' => __( 'Auf der Seite wurden ID-Attribute mehrfach verwendet. Doppelte IDs können dazu führen, dass Labelzuordnungen, ARIA-Referenzen und Anker-Links nicht korrekt funktionieren.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Stelle sicher, dass jede ID auf der Seite einzigartig ist. Verwende Klassen (class) für mehrfach verwendete Stile und IDs nur für einmalige Elemente.', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $dupe_list,
				'count'       => count( $dupes ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Rule: link_new_window (WCAG 3.2.2, minor)
	// -------------------------------------------------------------------------

	/**
	 * @param \DOMDocument $dom   Document.
	 * @param \DOMXPath    $xpath XPath.
	 * @param string       $url   Page URL.
	 * @return Issue|null
	 */
	private static function check_link_new_window( \DOMDocument $dom, \DOMXPath $xpath, $url ) {
		$nodes = $xpath->query( '//a[@target="_blank"]' );
		if ( ! $nodes || 0 === $nodes->length ) {
			return null;
		}

		$count   = 0;
		$context = '';

		foreach ( $nodes as $link ) {
			if ( ! ( $link instanceof \DOMElement ) ) {
				continue;
			}

			// Prüfe ob rel einen hinweisenden Wert hat (kein Sicherheitscheck, nur Hinweispflicht).
			$aria_label = strtolower( $link->getAttribute( 'aria-label' ) );
			$title      = strtolower( $link->getAttribute( 'title' ) );

			// Wenn aria-label oder title einen Hinweis auf neues Fenster enthält, OK.
			$hints = array( 'neues', 'new', 'fenster', 'window', 'tab', 'reiter' );
			$has_hint = false;
			foreach ( $hints as $hint ) {
				if ( false !== strpos( $aria_label, $hint ) || false !== strpos( $title, $hint ) ) {
					$has_hint = true;
					break;
				}
			}
			if ( $has_hint ) {
				continue;
			}

			$count++;
			if ( '' === $context ) {
				$context = self::outer_html( $link );
			}
		}

		if ( 0 === $count ) {
			return null;
		}

		return new Issue(
			array(
				'rule_id'     => 'link_new_window',
				'severity'    => 'minor',
				'wcag'        => '3.2.2',
				'title'       => __( 'Link öffnet neues Fenster ohne Warnung', 'barrierefrei-check' ),
				'description' => __( 'Ein oder mehrere Links mit target="_blank" weisen die Nutzerin nicht darauf hin, dass ein neues Fenster oder Tab geöffnet wird. Das kann für Screenreader-Nutzer verwirrend sein.', 'barrierefrei-check' ),
				'how_to_fix'  => __( 'Füge einen Hinweis im Linktext oder aria-label hinzu, z. B. "Webseite des Anbieters (öffnet in neuem Tab)" oder nutze ein Icon mit aria-label="neues Fenster".', 'barrierefrei-check' ),
				'url'         => $url,
				'context'     => $context,
				'count'       => $count,
			)
		);
	}
}
