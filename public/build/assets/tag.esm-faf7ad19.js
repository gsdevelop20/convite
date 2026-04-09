import{r as a,P as O,m as u,I as j,c as y}from"./app-f67868d2.js";import{C as S,u as w}from"./button.esm-54d4d14a.js";function s(t){"@babel/helpers - typeof";return s=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},s(t)}function h(t,e){if(s(t)!=="object"||t===null)return t;var n=t[Symbol.toPrimitive];if(n!==void 0){var r=n.call(t,e||"default");if(s(r)!=="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}function E(t){var e=h(t,"string");return s(e)==="symbol"?e:String(e)}function p(t,e,n){return e=E(e),e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}var x={value:"p-tag-value",icon:"p-tag-icon",root:function(e){var n,r=e.props;return y("p-tag p-component",(n={},p(n,"p-tag-".concat(r.severity),r.severity!==null),p(n,"p-tag-rounded",r.rounded),n))}},N=`
@layer primereact {
    .p-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .p-tag-icon,
    .p-tag-value,
    .p-tag-icon.pi {
        line-height: 1.5;
    }
    
    .p-tag.p-tag-rounded {
        border-radius: 10rem;
    }
}
`,l=S.extend({defaultProps:{__TYPE:"Tag",value:null,severity:null,rounded:!1,icon:null,style:null,className:null,children:void 0},css:{classes:x,styles:N}});function f(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter(function(o){return Object.getOwnPropertyDescriptor(t,o).enumerable})),n.push.apply(n,r)}return n}function _(t){for(var e=1;e<arguments.length;e++){var n=arguments[e]!=null?arguments[e]:{};e%2?f(Object(n),!0).forEach(function(r){p(t,r,n[r])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):f(Object(n)).forEach(function(r){Object.defineProperty(t,r,Object.getOwnPropertyDescriptor(n,r))})}return t}var T=a.forwardRef(function(t,e){var n=a.useContext(O),r=l.getProps(t,n),o=l.setMetaData({props:r}),c=o.ptm,i=o.cx,g=o.isUnstyled;w(l.css.styles,g,{name:"tag"});var m=a.useRef(null),v=u({className:i("icon")},c("icon")),b=j.getJSXIcon(r.icon,_({},v),{props:r});a.useImperativeHandle(e,function(){return{props:r,getElement:function(){return m.current}}});var d=u({ref:m,className:y(r.className,i("root")),style:r.style},l.getOtherProps(r),c("root")),P=u({className:i("value")},c("value"));return a.createElement("span",d,b,a.createElement("span",P,r.value),a.createElement("span",null,r.children))});T.displayName="Tag";export{T};
