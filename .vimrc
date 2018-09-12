set nocompatible              " be iMproved, required
filetype off                  " required

" set the runtime path to include Vundle and initialize
set rtp+=~/.vim/bundle/Vundle.vim
call vundle#begin()
" alternatively, pass a path where Vundle should install plugins
"call vundle#begin('~/some/path/here')

" let Vundle manage Vundle, required
Plugin 'VundleVim/Vundle.vim'

" The following are examples of different formats supported.
" Keep Plugin commands between vundle#begin/end.
" plugin on GitHub repo
Plugin 'tpope/vim-fugitive'
" plugin from http://vim-scripts.org/vim/scripts.html
" Plugin 'L9'
" Git plugin not hosted on GitHub
Plugin 'git://git.wincent.com/command-t.git'
" The sparkup vim script is in a subdirectory of this repo called vim.
" Pass the path to set the runtimepath properly.
Plugin 'rstacruz/sparkup', {'rtp': 'vim/'}
" Install L9 and avoid a Naming conflict if you've already installed a
" different version somewhere else.
" Plugin 'ascenator/L9', {'name': 'newL9'}

" MY PLUGINS
Plugin 'vim-scripts/Align'
Bundle 'joonty/vim-phpqa.git'
Plugin 'spf13/PIV'
Plugin 'ohjames/tabdrop'

" All of your Plugins must be added before the following line
call vundle#end()            " required
filetype plugin indent on    " required
" To ignore plugin indent changes, instead use:
"filetype plugin on
"
" Brief help
" :PluginList       - lists configured plugins
" :PluginInstall    - installs plugins; append `!` to update or just :PluginUpdate
" :PluginSearch foo - searches for foo; append `!` to refresh local cache
" :PluginClean      - confirms removal of unused plugins; append `!` to auto-approve removal
"
" see :h vundle for more details or wiki for FAQ
" Put your non-Plugin stuff after this line

let g:phpqa_codesniffer_autorun = 0
let g:phpqa_messdetector_autorun = 0
let php_folding=0
let g:DisableAutoPHPFolding = 1
noremap ; K
noremap <F12> <Esc>:syntax sync fromstart<CR>

" Read vim config if set in first 3 lines of file.
set modeline
set modelines=3

" Set indentation
set tabstop=4
set expandtab
set shiftwidth=4
set softtabstop=4
set smartindent

" Enable bar at the bottom
set laststatus=2

" Set various file options
set undofile
set undodir=~/.vim/undodir//
set backupdir=~/.vim/backupdir//
set directory=~/.vim/swapdir//

" Disable arrow keys
noremap <Up> <NOP>
noremap <Down> <NOP>
noremap <Left> <NOP>
noremap <Right> <NOP>

" Fix tab completion
set wildmode=longest,list,full
set spell

set breakindent
set formatoptions=1
set lbr
set hlsearch

set pastetoggle=<F1>
