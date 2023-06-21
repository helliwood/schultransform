import Vue from 'vue';
import Snippet from "./components/Snippet";
import SnippetContentArea from "./components/SnippetContentArea";
import ContentTree from "./components/ContentTree";
import ContentTreeSiteForm from "./components/ContentTreeSiteForm";
import ContentTreeSiteFormParent from "./components/ContentTreeSiteFormParent";
import SnippetLink from "./components/SnippetLink";
import SnippetTree from "./components/SnippetTree";

Vue.component(ContentTree.name, ContentTree);
Vue.component(Snippet.name, Snippet);
Vue.component(SnippetTree.name, SnippetTree);
Vue.component(SnippetContentArea.name, SnippetContentArea);
Vue.component(SnippetLink.name, SnippetLink);
Vue.component(ContentTreeSiteForm.name, ContentTreeSiteForm);
Vue.component(ContentTreeSiteFormParent.name, ContentTreeSiteFormParent);