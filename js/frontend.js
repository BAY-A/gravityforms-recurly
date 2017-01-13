/**
 * Front-end Script
 */

window.GFRecurly = null;

(function($){

		updateHiddenCCFieldVersions = function( sel ) {

			var source_sel_id = $( sel ).attr( 'id' );
			var hidden_version_id = '#' + source_sel_id + '_hidden';
			var source_sel_val = $( sel ).val();

			$( hidden_version_id ).attr( 'value', source_sel_val );
		}

    GFRecurly = function( args ) {

        for( var prop in args ) {
            if( args.hasOwnProperty( prop ) )
                this[prop] = args[prop];
        }

        this.form = null;

        this.init = function() {

            if( ! this.isCreditCardOnPage() )
                return;

						var GFRecurlyObj = this;

            recurly.configure( {
							publicKey: this.api_key,
							currency: 'USD',
							required: ['cvv']
						} );

            // initialize spinner
            if( ! this.isAjax )
                gformInitSpinner( this.formId );

            // bind Recurly functionality to submit event
            $( '#gform_' + this.formId ).submit( function( event ){
                if ($(this).data('GFRecurlysubmitting') || $('#gform_save_' + GFRecurlyObj.formId).val() == 1) {
                    return;
                } else {
                    event.preventDefault();
                    $(this).data('GFRecurlysubmitting', true);
                }

                var form = $(this),
                    ccInputPrefix = 'input_' + GFRecurlyObj.formId + '_' + GFRecurlyObj.ccFieldId + '_';


                GFRecurlyObj.form = form;

								//GET RECURLY TOKEN HERE
								recurly.token( $( '#gform_' + this.formId )[0], function ( err, token ) {
							  	GFRecurlyObj.responseHandler( err, token );
							  });

            } );

        };

        this.responseHandler = function( err, token ) {

						var final_response = token;

						if( err ){
							console.log(err);
							final_response = err;
						}

            var form = this.form,
                ccInputPrefix = 'input_' + this.formId + '_' + this.ccFieldId + '_',
                ccInputSuffixes = [ '1', '2_month', '2_year', '3', '5' ],
                cardType = false;

            // remove "name" attribute from credit card inputs
            for( var i = 0; i < ccInputSuffixes.length; i++ ) {

                var input = form.find( '#' + ccInputPrefix + ccInputSuffixes[i] );

                if( ccInputSuffixes[i] == '1' ) {

                    var ccNumber = $.trim( input.val() ),
                        cardType = gformFindCardType( ccNumber );

                    if( typeof this.cardLabels[cardType] != 'undefined' )
                        cardType = this.cardLabels[cardType];

                    form.append( $( '<input type="hidden" name="recurly_credit_card_last_four" />' ).val( ccNumber.slice( -4 ) ) );
                    form.append( $( '<input type="hidden" name="recurly_credit_card_type" />' ).val( cardType ) );

                }

                // name attribute is now removed from markup in GFRecurly::add_recurly_inputs()
                //input.attr( 'name', null );

            }

            // append recurly.js response
            form.append( $( '<input type="hidden" name="recurly_response" />' ).val( final_response ) );

            // submit the form
            form.submit();

        }

        this.isLastPage = function() {

            var targetPageInput = $( '#gform_target_page_number_' + this.formId );
            if( targetPageInput.length > 0 )
                return targetPageInput.val() == 0;

            return true;
        }

        this.isCreditCardOnPage = function() {

            var currentPage = this.getCurrentPageNumber();

            // if current page is false or no credit card page number, assume this is not a multi-page form
            if( ! this.ccPage || ! currentPage )
                return true;

            return this.ccPage == currentPage;
        }

        this.getCurrentPageNumber = function() {
            var currentPageInput = $( '#gform_source_page_number_' + this.formId );
            return currentPageInput.length > 0 ? currentPageInput.val() : false;
        }

        this.init();

    }

})(jQuery);
