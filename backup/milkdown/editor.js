import { Editor } from '@milkdown/kit/core';
import { commonmark } from '@milkdown/kit/preset/commonmark';
import { history } from '@milkdown/kit/plugin/history';

import { nord } from '@milkdown/theme-nord';
import '@milkdown/theme-nord/style.css';

const milkdown = Editor
  .make()
  .config(nord)
  .use(commonmark)
  .use(history)
  .create()
  .then(() => {
    console.log('Editor created');
  });

// To destroy the editor
milkdown.destroy();
