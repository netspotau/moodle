/*
Copyright (c) 2010, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.com/yui/license.html
version: 3.1.1
build: 47
*/
YUI.add("tabview",function(B){var C=B.ClassNameManager.getClassName,F="tabview",L="tab",N="content",K="panel",H="selected",I={},J=".",D={tabview:C(F),tabviewPanel:C(F,K),tabviewList:C(F,"list"),tab:C(L),tabLabel:C(L,"label"),tabPanel:C(L,K),selectedTab:C(L,H),selectedPanel:C(L,K,H)},E={tabview:J+D.tabview,tabviewList:"> ul",tab:"> ul > li",tabLabel:"> ul > li > a ",tabviewPanel:"> div",tabPanel:"> div > div",selectedTab:"> ul > "+J+D.selectedTab,selectedPanel:"> div "+J+D.selectedPanel},A=function(O){this.init.apply(this,arguments);};A.NAME="tabviewBase";A._queries=E;A._classNames=D;B.mix(A.prototype,{init:function(O){O=O||I;this._node=O.host||B.one(O.node);this.refresh();},initClassNames:function(O){B.Object.each(E,function(R,Q){if(D[Q]){var P=this.all(R);if(O!==undefined){P=P.item(O);}if(P){P.addClass(D[Q]);}}},this._node);this._node.addClass(D.tabview);},_select:function(P){var S=this._node,T=S.one(E.selectedTab),R=S.one(E.selectedPanel),Q=S.all(E.tab).item(P),O=S.all(E.tabPanel).item(P);if(T){T.removeClass(D.selectedTab);}if(R){R.removeClass(D.selectedPanel);}if(Q){Q.addClass(D.selectedTab);}if(O){O.addClass(D.selectedPanel);}},initState:function(){var P=this._node,Q=P.one(E.selectedTab),O=Q?P.all(E.tab).indexOf(Q):0;this._select(O);},_scrubTextNodes:function(){this._node.one(E.tabviewList).get("childNodes").each(function(O){if(O.get("nodeType")===3){O.remove();}});},refresh:function(){this._scrubTextNodes();this.initClassNames();this.initState();this.initEvents();},tabEventName:"click",initEvents:function(){this._node.delegate(this.tabEventName,this.onTabEvent,E.tab,this);},onTabEvent:function(O){O.preventDefault();this._select(this._node.all(E.tab).indexOf(O.currentTarget));},destroy:function(){this._node.detach(this.tabEventName);}});B.TabviewBase=A;var E=B.TabviewBase._queries,D=B.TabviewBase._classNames,J=".",C=B.ClassNameManager.getClassName,M=B.Base.create("tabView",B.Widget,[B.WidgetParent],{_afterChildAdded:function(O){this.get("contentBox").focusManager.refresh();},_afterChildRemoved:function(Q){var O=Q.index,P=this.get("selection");if(!P){P=this.item(O-1)||this.item(0);if(P){P.set("selected",1);}}this.get("contentBox").focusManager.refresh();},_initAria:function(){var O=this.get("contentBox"),P=O.one(E.tabviewList);if(P){P.setAttrs({role:P});}},bindUI:function(){this.get("contentBox").plug(B.Plugin.NodeFocusManager,{descendants:J+D.tabLabel,keys:{next:"down:39",previous:"down:37"},circular:true});this.after("addChild",this._afterChildAdded);this.after("removeChild",this._afterChildRemoved);},renderUI:function(){var O=this.get("contentBox");this._renderListBox(O);this._renderPanelBox(O);this._renderTabs(O);this._setDefSelection(O);},_setDefSelection:function(O){var P=this.get("selection")||this.item(0);this.some(function(Q){if(Q.get("selected")){P=Q;return true;}});if(P){P.set("selected",1);}},_renderListBox:function(O){var P=O.one(E.tabviewList);if(!P){P=O.appendChild(B.Node.create(M.LIST_TEMPLATE));}else{P.addClass(D.tabviewList);}this._childrenContainer=P;},_renderPanelBox:function(P){var O=P.one(E.tabviewPanel);if(!O){P.append(M.PANEL_TEMPLATE);}else{O.addClass(D.tabviewPanel);}},_renderTabs:function(O){var Q=O.all(E.tab),P=O.all(E.tabPanel),R=this;if(Q){Q.addClass(D.tab);O.all(E.tabLabel).addClass(D.tabLabel);O.all(E.tabPanel).addClass(D.tabPanel);Q.each(function(U,T){var S=P.item(T);R.add({boundingBox:U,contentBox:U.one(J+D.tabLabel),label:U.one(J+D.tabLabel).get("text"),panelNode:S});});}}},{LIST_TEMPLATE:'<ul class="'+D.tabviewList+'"></ul>',PANEL_TEMPLATE:'<div class="'+D.tabviewPanel+'"></div>',ATTRS:{defaultChildType:{value:"Tab"},tabIndex:{value:null}}});B.TabView=M;var G=B.Lang,E=B.TabviewBase._queries,D=B.TabviewBase._classNames,C=B.ClassNameManager.getClassName;B.Tab=B.Base.create("tab",B.Widget,[B.WidgetChild],{BOUNDING_TEMPLATE:'<li class="'+D.tab+'"></li>',CONTENT_TEMPLATE:'<a class="'+D.tabLabel+'"></a>',PANEL_TEMPLATE:'<div class="'+D.tabPanel+'"></div>',_uiSetSelectedPanel:function(O){this.get("panelNode").toggleClass(D.selectedPanel,O);},_afterTabSelectedChange:function(O){this._uiSetSelectedPanel(O.newVal);},_afterParentChange:function(O){if(!O.newVal){this._remove();}else{this._add();}},_initAria:function(){var P=this.get("contentBox"),Q=P.get("id"),O=this.get("panelNode");if(!Q){Q=B.guid();P.set("id",Q);}P.set("role","tab");P.get("parentNode").set("role","presentation");O.setAttrs({role:"tabpanel","aria-labelledby":Q});},syncUI:function(){this.set("label",this.get("label"));this.set("content",this.get("content"));this._uiSetSelectedPanel(this.get("selected"));},bindUI:function(){this.after("selectedChange",this._afterTabSelectedChange);this.after("parentChange",this._afterParentChange);},renderUI:function(){this._renderPanel();this._initAria();},_renderPanel:function(){this.get("parent").get("contentBox").one(E.tabviewPanel).appendChild(this.get("panelNode"));},_add:function(){var O=this.get("parent").get("contentBox"),Q=O.one(E.tabviewList),P=O.one(E.tabviewPanel);if(Q){Q.appendChild(this.get("boundingBox"));}if(P){P.appendChild(this.get("panelNode"));}},_remove:function(){this.get("boundingBox").remove();this.get("panelNode").remove();},_onActivate:function(O){if(O.target===this){O.domEvent.preventDefault();O.target.set("selected",1);}},initializer:function(){this.publish(this.get("triggerEvent"),{defaultFn:this._onActivate});},_defLabelSetter:function(O){this.get("contentBox").setContent(O);return O;},_defContentSetter:function(O){this.get("panelNode").setContent(O);return O;},_defPanelNodeValueFn:function(){var Q,P=this.get("contentBox").get("href")||"",O;if(P.charAt(0)==="#"){Q=P.substr(1);O=B.one(P);}else{Q=B.guid();}if(!O){O=B.Node.create(this.PANEL_TEMPLATE);O.set("id",Q);}return O;}},{ATTRS:{triggerEvent:{value:"click"},label:{setter:"_defLabelSetter",validator:G.isString},content:{setter:"_defContentSetter",validator:G.isString},panelNode:{valueFn:"_defPanelNodeValueFn"},tabIndex:{value:null,validator:"_validTabIndex"}},HTML_PARSER:{selected:function(O){return this.get("boundingBox").hasClass(D.selectedTab);
}}});},"3.1.1",{requires:["substitute","node-focusmanager","tabview-base","widget","widget-parent","widget-child"]});