<?php
/**
 * @package Dynamic Price Updater
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * @version 4.0
 * @licence This module is released under the GNU/GPL licence
 */

if (defined('DPU_STATUS') && DPU_STATUS === 'true') {
  $load = true; // if any of the PHP conditions fail this will be set to false and DPU won't be fired up
  $pid = (!empty($_GET['products_id']) ? (int)$_GET['products_id'] : 0);
  if (0 == $pid) {
    $load = false;
  } elseif (zen_get_products_price_is_call($pid) || zen_get_products_price_is_free($pid) || STORE_STATUS > 0) {
    $load = false;
  }
  $pidp = zen_get_products_display_price($pid);
  if (empty($pidp)) {
    $load = false;
  }

  if ($load) {
    if (!defined('DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY')) {
      define('DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY', 'productDetailsList_product_info_quantity');
    }
    ?>
<script type="text/javascript">
      // Set some global vars
      const theFormName = "<?php echo DPU_PRODUCT_FORM; ?>";
      let theForm = false;
      let _secondPrice = <?php echo (DPU_SECOND_PRICE !== '' ? '"' . DPU_SECOND_PRICE . '"' : 'false'); ?>;
      let objSP = false; // please don't adjust this
      // Updater sidebox settings
      let objSB = false;
    <?php
// this holds the sidebox object // IE. Left sidebox false should become document.getElementById('leftBoxContainer');
// For right sidebox, this should equal document.getElementById('rightBoxContainer');
// Perhaps this could be added as an additional admin configuration key.  The result should end up being that a new SideBox is added
// before whatever is described in this "search".  So this may actually need to be a div within the left or right boxes instead of the
// left or right side box.
//   May also be that this it is entirely unnecessary to create a sidebox when one could already exist based on the file structure.

    if (DPU_SHOW_LOADING_IMAGE === 'true') { // create the JS object for the loading image 
      ?>
        const imgLoc = "replace"; // Options are "replace" or , "" (empty)

        let origPrice;
        let loadImg = document.createElement("img");
        loadImg.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
        loadImg.id = "DPULoaderImage";

        let loadImgSB = document.createElement("img");
        loadImgSB.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
        loadImgSB.id = "DPULoaderImageSB";
        loadImgSB.style.margin = "auto";
        // loadImg.style.display = 'none';
    <?php } ?>

      function getPrice() {
        let pspClass = false;
    <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>

          let psp = false;
          let thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
          let test = false;
          if (thePrice) {
            test = thePrice.getElementsByTagName("span");
          }
          let a;
          let b = test.length;

          for (a = 0; a < b; a += 1) {
            if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
              psp = test[a];
            }
          }
          if (!psp) {
            psp = thePrice;
          }
          if (psp) {
            pspClass = psp.className;
            origPrice = psp.innerHTML;
          }
          if (psp && imgLoc === "replace") {
            if (thePrice) {
              loadImg.style.display = "inline"; //'block';
              let pspStyle = psp.currentStyle || window.getComputedStyle(psp);
              loadImg.style.height = pspStyle.lineHeight; // Maintains the height so that there is not a vertical shift of the content.
              origPrice = psp.innerHTML;
              updateInnerHTML(loadImg.outerHTML, false, psp, true);
            }

          } else {
            document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>").appendChild(loadImg);
          }
          let theSB;
          if (document.getElementById("dynamicpriceupdatersidebox")) {
            theSB = document.getElementById("dynamicpriceupdatersideboxContent");
            updateInnerHTML("", false, theSB, true);
            theSB.style.textAlign = "center";
            theSB.appendChild(loadImgSB);
          }
    <?php } ?>
        const n = theForm.elements.length;
        let attributes = '';
        let el;
        let i;
        let aName;

        for (i = 0; i < n; i += 1) {
          el = theForm.elements[i];
          switch (el.type) {
            /* I'm not sure this even needed as a switch; testing needed*/
            case "select":
            case "select-one":
            case "textarea":
            case "text":
            case "number":
            case "hidden":
              if (el.name.startsWith("id[")) { // Ensure not to replace an existing value. I.e. drop a duplicate value.
                aName = el.name;
                attributes += aName + '~' + el.value + '|';
              }
              break;
            case "checkbox":
            case "radio":
              if (true === el.checked) {
                if (!(el.name in attributes) && el.name.startsWith("id[")) { // Ensure not to replace an existing value. I.e. drop a duplicate value.
                  aName = el.name;
                  attributes += aName + '~' + el.value + '|';
                }
              }
              break;
          }
        }
        const products_id = <?php echo (int)$pid; ?>;

        var _this = this; // scope resolution

        zcJS.ajax({
          url: 'ajax.php?act=DPU_Ajax&method=getDetails',
          data: {
            products_id: products_id,
            attributes: attributes,
            pspClass: pspClass
          }
        }).done(function (resultArray) {
          handlePrice(resultArray);
        }).fail(function (jqXHR, textStatus, errorThrown) {
    <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>
            const thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
            let test = thePrice.getElementsByTagName("span");
            let psp = false;
            let a;
            let b = test.length;

            for (a = 0; a < b; a += 1) {
              if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
                psp = test[a];
              }
            }

            if (typeof (loadImg) !== "undefined" && loadImg.parentNode !== null && loadImg.parentNode.id === thePrice.id && imgLoc !== "replace") {
              if (psp) {
                psp.removeChild(loadImg);
              } else {
                thePrice.removeChild(loadImg);
              }
            } else if (typeof (loadImg) !== "undefined" && imgLoc === "replace") {
              updateInnerHTML(origPrice, psp, thePrice);
            }
            if (_secondPrice !== false) {
              updSP();
            }

    <?php } ?>
          //alert("Status returned - " + textStatus);
        });
      }

      function updateInnerHTML(storeVal, psp, obj, replace) {
        if (typeof (replace) === "undefined") {
          replace = true;
        }
        if (storeVal !== "") {
          if (psp) {
            if (replace) {
              psp.innerHTML = storeVal;
            } else {
              psp.innerHTML += storeVal;
            }
          } else {
            if (replace) {
              obj.innerHTML = storeVal;
            } else {
              obj.innerHTML += storeVal;
            }
          }

          if (_secondPrice !== false) {
            updSP();
          }
        }
      }

      function handlePrice(results) {
        var thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
        if (typeof (loadImg) !== "undefined" && loadImg.parentNode !== null && loadImg.parentNode.id === thePrice.id && imgLoc !== "replace") {
          thePrice.removeChild(loadImg);
        }

        // use the spans to see if there is a discount occuring up in this here house
        let test = thePrice.getElementsByTagName("span");
        let psp = false;
        let a;
        let b = test.length;
        let pdpt = false;

        for (a = 0; a < b; a += 1) {
          if (test[a].className === "normalprice") {
            pdpt = test[a];
          }
          if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
            psp = test[a];
          }
        }

        let updateSidebox;
        let type = results.responseType;
          let sbContent = "";
let theSB;

        if (document.getElementById("dynamicpriceupdatersidebox")) {
          theSB = document.getElementById("dynamicpriceupdatersideboxContent");
          theSB.style.textAlign = "left";
          updateSidebox = true;
        } else {
          updateSidebox = false;
        }
        if (type === "error") {
          showErrors();
        } else {
          let temp;
          temp = results.data;

          let storeVal;
          let i;
          for (i in temp) {
            type = i;
            storeVal = temp[i];
            switch (type) {
              // the 'type' attribute defines what type of information is being provided

              case "preDiscPriceTotal":
                if (pdpt) {
                  updateInnerHTML(storeVal, pdpt, thePrice, true);
                }
                break;
              case "preDiscPriceTotalText":
                if (pdpt) {
                  if (thePrice.firstChild.nodeType === 3) {
                    thePrice.firstChild.nodeValue = storeVal;
                  }
                }
                break;
              case "priceTotal":
                updateInnerHTML(storeVal, psp, thePrice, true);
                break;
              case "quantity":
                updateInnerHTML(storeVal, psp, thePrice, false);
                break;
              case "weight":
                var theWeight = document.getElementById("<?php echo DPU_WEIGHT_ELEMENT_ID; ?>");
                if (theWeight) {
                  updateInnerHTML(storeVal, false, theWeight, true);
                }
                break;
              case "sideboxContent":
                if (updateSidebox) {
                  sbContent += storeVal;
                }
                break;
              case "stock_quantity":
                var theStockQuantity = document.getElementById("<?php echo DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY; ?>");
                if (theStockQuantity) {
                  updateInnerHTML(storeVal, false, theStockQuantity, true);
                }
                break;
            }
          }
        }
        if (updateSidebox) {
          updateInnerHTML(sbContent, false, theSB, true);
        }
      }

      function updSP() {
        // adjust the second price display; create the div if necessary
        let flag = false; // error tracking flag

        if (_secondPrice !== false) { // second price is active
          let centre = document.getElementById("productGeneral");
          let temp = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
          let itemp = document.getElementById(_secondPrice);
          flag = false;

          if (objSP === false) { // create the second price object
            if (!temp || !itemp) {
              flag = true;
            }

            if (!flag) {
              objSP = temp.cloneNode(true);
              objSP.id = temp.id + "Second";
              itemp.parentNode.insertBefore(objSP, itemp.nextSibling);
            }
          }
          objSP.innerHTML = temp.innerHTML;
        }
      }

      function createSB() { // create the sidebox for the attributes info display
        if (!(document.getElementById("dynamicpriceupdatersidebox")) && objSB) {
          let tempC = document.createElement("div");
          tempC.id = "dynamicpriceupdatersideboxContent";
          tempC.className = "sideBoxContent";
          tempC.innerHTML = "If you can read this Chrome has broken something";
          objSB.appendChild(tempC);

          temp.parentNode.insertBefore(objSB, temp);
        }
      }

      function showErrors() {
        let alertText = "";
        let errVal;
        let errorText;
        let i;

        errorText = this.responseJSON.responseText;

        for (i in errorText) {
          if (!(errorText.hasOwnProperty(i))) {
            continue;
          }
          errVal = i;
          alertText += "\n- " + errVal;
        }
        alert("Error! Message reads:\n\n" + alertText);
      }

      function init() {
        let n = document.forms.length;
        let i;
        for (i = 0; i < n; i += 1) {
          if (document.forms[i].name === theFormName) {
            theForm = document.forms[i];
          }
        }

        n = theForm.elements.length;
        for (i = 0; i < n; i += 1) {
          switch (theForm.elements[i].type) {
            case "select":
            case "select-one":
              theForm.elements[i].addEventListener("change", function () {
                getPrice();
              });
              break;
            case "textarea":
            case "text":
              theForm.elements[i].addEventListener("input", function () {
                getPrice();
              });
              break;
            case "checkbox":
            case "radio":
              theForm.elements[i].addEventListener("click", function () {
                getPrice();
              });
              break;
            case "number":
              theForm.elements[i].addEventListener("change", function () {
                getPrice();
              });
              theForm.elements[i].addEventListener("keyup", function () {
                getPrice();
              });
              theForm.elements[i].addEventListener("input", function () {
                getPrice();
              });
              break;
          }
        }

        createSB();

        getPrice();
      }
    </script>
    <?php
  }
}