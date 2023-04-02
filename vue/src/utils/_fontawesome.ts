/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core';

/* import font awesome icon component */
export { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

/* import icon pack */
import { fas } from '@fortawesome/free-solid-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';

/* add icons to the library, so we can use them in the vue component */
library.add(fas, far);
